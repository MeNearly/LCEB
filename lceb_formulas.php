<?php
namespace xylian\graph;
/*################################################
;###                                          ###
;##                LCEB GRAPH                  ##
;#                    v1.5                      #
;#             (formulas display)               #
;##        (c) 2022 MICHAUD Yannick            ##
;###                                          ###
;################################################*/
require_once "graph.php";
require_once dirname(__FILE__)."/../args.php";
// SANS puissance
const ops=array("+" => "add", "-" => "sub", "*" => "mul", "/" => "div");

// AVEC puissance
const ops_pow=array("+" => "add", "-" => "sub", "*" => "mul", "/" => "div", "^" => "pow");

const DEBUG=false;
const DEBUG2=false;

function removeNumberAt(array $arr, int $index):array {
  $l=count($arr);
  $result=array();
  for ($i=0;$i<$l;$i++) {
    if ($i!=$index)
      $result[]=$arr[$i];
  }
  return $result;
}

function removeFirstNumber(array $arr, NumberAtom $number):array {
  $result=array();
  $removed=false;
  foreach ($arr as $value) {
    if ($value->getValue()!=$number->getValue() || $removed) {
      $result[]=$value;
    } else {
      $removed=true;
    }
  }
  return $result;
}

class NumberAtom {
  private $value;
  private $text;
  public function __construct($v, string $txt) {
    $this->value=$v;
    $this->text=$txt;
  }
  public function setText(string $txt) {
    $this->text=$text;
  }
  public function setValue($v) {
    $this->value=$v;
  }
  public function getValue() {
    return $this->value;
  }
  public function getText():string {
    return $this->text;
  }
  public function __toString():string {
    return "$this->value";
  }
}

const zero=new NumberAtom(0,"0");

class LcebNode extends Node {
  public function __construct(string $name="noName",string $label="",$value="") {
    parent::__construct($name,$label,is_array($value)?$value:array(0,0,0,0,0,array()));
    if ($name=="")
      $this->setName("LCEB ".$this->getId());
    if ($label=="")
      $this->setLabel("LCEB ".$this->getId());
  }
// CORE
  public function getNumber():NumberAtom {
    return $this->value[0];
  }
  public function getOperation():string {
    return $this->value[1];
  }
  public function getOperande():NumberAtom {
    return $this->value[2];
  }
  public function getResult():NumberAtom {
    return $this->value[3];
  }
  public function getTarget():NumberAtom {
    return $this->value[4];
  }
  public function getRemaining():array {
    return $this->value[5];
  }

  public function display():string {
    return $this->getNumber()->getValue()." ".$this->getOperation()." ".$this->getOperande()->getValue()." = ".$this->getResult()->getValue();
  }

  public function setNumber (NumberAtom $value) {
    $this->value[0]=$value;
  }
  public function setOperation(string $value) {
    $this->value[1]=$value;
  }
  public function setOperande(NumberAtom $value) {
    $this->value[2]=$value;
  }
  public function setResult(NumberAtom $value) {
    $this->value[3]=$value;
  }
  public function setTarget(NumberAtom $value) {
    $this->value[4]=$value;
  }
  public function setRemaining($value=array()) {
    $this->value[5]=$value;
  }

  public function filterNumbers(array $numbers):array {
    $arr=$this->getRemaining();
    $l_r=count($arr);
    $diff=array_values($arr);
    foreach ($numbers as $value)
      $diff=removeFirstNumber($diff,$value);
    $diff=removeFirstNumber($diff,$this->getResult());
    return $diff;
  }

  public function displayPrevious():string {
    if (count($this->getPrevious())==0) {
      return "";
    } else {
      $prev=$this->getPrevious()[0];
      $result="";
      if ($prev!==NULL)
        $result=$prev->displayPrevious();
      $tmp=$this->display().PHP_EOL;
      $result=$result.$tmp;
      return $result;
    }
  }
}

function add (NumberAtom $a, NumberAtom $b) {
  if ($a->getValue()!=0 && $b->getValue()!=0) {
    return new NumberAtom($a->getValue()+$b->getValue(),"(".$a->getText()."+".$b->getText().")");
  }
  return false;
}
function sub (NumberAtom $a, NumberAtom $b) {
  $tmp=$a->getValue()-$b->getValue();
  if ($tmp>=0) {
    return new NumberAtom($a->getValue()-$b->getValue(),"(".$a->getText()."-".$b->getText().")");
  }
  return false;
}
function mul (NumberAtom $a, NumberAtom $b) {
  if ($a->getValue()>1 && $b->getValue()>1) {
    return new NumberAtom($a->getValue()*$b->getValue(),"(".$a->getText()."*".$b->getText().")");
  }
  return false;
}
function div (NumberAtom $a, NumberAtom $b) {
  $a_=$a->getValue();
  $b_=$b->getValue();
  if ($b_==0) return false;
  if ($a_!=1 && $b_!=1) {
    $tmp=intval($a_/$b_);
    if (($tmp*$b_) == $a_) {
      return new NumberAtom($tmp,"(".$a->getText()."/".$b->getText().")");
    }
  }
  return false;
}
function pow (NumberAtom $a, NumberAtom $b) {
  $a_=$a->getValue();
  $b_=$b->getValue();
  if ($a_!=1 && $b_!=1) {
    $tmp=intval(\pow($a_,$b_));
    if ($tmp==\pow($a_,$b_))
      return new NumberAtom($tmp,"(".$a->getText()."^".$b->getText().")");
  }
  return false;
}

class LCEB {

  private $operations;
  private $graph;
  private $solved;
  private $target;
  private $initNumbers;
  private $shortest;
  private $shortest_l;
  private $longest;
  private $longest_l;
  private $threshold;
  private $best;

  public function __construct(NumberAtom $target, array $numbers, array $opers=\xylian\graph\ops) {
    $this->operations=$opers;
    $graph=new Graph();
    arsort($numbers);
    $start=new LcebNode("Start","Début",array(zero,0,zero,zero,$target,$numbers));
    $graph->addNode($start);
    $this->graph=$graph;
    $this->target=$target;
    $this->initNumbers=$numbers;
    $this->shortest=0;
    $this->longest_l=0;
    $this->threshold=0;
  }
  private function storeSolution(\xylian\graph\LcebNode $node,int $length) {
    if ($this->shortest_l==0 || $length<=$this->shortest_l) {
      if ($this->shortest != NULL) {
        $this->getGraph()->unLinkNode($this->shortest);
        unset($this->shortest);
      }
      $this->shortest=$node;
      $this->shortest_l=$length;
    }
    if ($length>=$this->longest_l) {
      if ($this->longest != NULL) {
        $this->getGraph()->unLinkNode($this->longest);
        unset($this->longest);
      }
      $this->longest=$node;
      $this->longest_l=$length;
    }
  }
  private function storeBest(\xylian\graph\LcebNode $node) {
    if (abs($node->getResult()->getValue()-$this->target->getValue())>$this->threshold) {
      return;
    } else {
      if ($this->best != NULL) {
        $this->getGraph()->unLinkNode($this->best);
        unset($this->best);
      }
      $this->best=$node;
    }
  }
  public function getGraph():Graph {
    return $this->graph;
  }
  public function getTarget():NumberAtom {
    return $this->target;
  }
  public function getInitNumbers():array {
    return $this->initNumbers;
  }
  public function isSolved():bool {
    return boolval($this->solved);
  }
  public function setSolved(bool $sol) {
    $this->solved=$sol;
  }
  private function explore(\xylian\graph\LcebNode $node, int $op_index=0):bool {
    $target=$node->getTarget();
    $rem=$node->getRemaining();
    $v=$node->getResult();
    $l=count($rem);
    // CONDITIONS D'Arrêt
    if ($v->getValue()==$target->getValue()) {
      if ($this->threshold>0) { // Si on avait mis un seuil mais qu'on a trouvé exactement
      // On passe le seuil à 0 et on sauvegarde
        $this->threshold=0;
        $this->storeBest($node);
        $this->setSolved(true);
        return true;
      }
      $tmp=$node->filterNumbers($this->getInitNumbers());
      if (count($tmp)>0) {
        if ($this->threshold==0) {
          return false;
        }
      } else {
        $this->storeSolution($node,$op_index);
        $this->setSolved(true);
        return true;
      }
    }
    if ($l==1) { // Car il y a le résultat de l'opération précédente
      if ($this->threshold>0 && (abs($v->getValue() - $target->getValue()) <= $this->threshold)) {
        $this->storeBest($node);
        return true;
      }
      return false;
    }
    $tmp_solved=false;
    for ($i=0;$i<$l;$i++) {
      for ($j=$i+1;$j<$l;$j++) {
        foreach ($this->operations as $symbol => $func) {
          $newNode=NULL;
          $function="\\".__NAMESPACE__."\\".$func;
          $result=$function($rem[$i],$rem[$j]);
          if ($result!==false) {
            $remainings=removeNumberAt($rem,$i);
            $remainings=removeNumberAt($remainings,$j-1);
            $remainings[]=$result;
            rsort($remainings);
            $newNode=new LcebNode("","",array($rem[$i],$symbol,$rem[$j],$result,$target,$remainings));
            if ($this->explore($newNode,($op_index+1))) {
              $tmp_solved=true;
              $this->getGraph()->addNode($newNode,false);
              $this->getGraph()->linkNodes($node,$newNode);
            } else {
              unset($newNode);
            }
          }
          if ($symbol=="-" || $symbol=="/" || $symbol=="^") {
            $remainings=removeNumberAt($rem,$i);
            $remainings=removeNumberAt($remainings,$j-1);
            $result=$function($rem[$j],$rem[$i]);
            if ($result!==false) {
              $remainings[]=$result;
              rsort($remainings);
              $newNode=new LcebNode("","",array($rem[$j],$symbol,$rem[$i],$result,$target,$remainings));
              if ($this->explore($newNode,($op_index+1))) {
                $tmp_solved=true;
                $this->getGraph()->addNode($newNode,false);
                $this->getGraph()->linkNodes($node,$newNode);
              } else {
                unset($newNode);
              }
            }
          }
        }
      }
    }
    // To tell if current node has a child which solves...
    return $tmp_solved;
  }
  public function displayLLSolutions(bool $interactive=true, int $delta=0) {
    if (!$this->isSolved()) {
      echo "Pas de solution....".PHP_EOL;
      if (!$interactive)
        return;
      echo "Chercher une solution approchée ? [O/n]".PHP_EOL;
      $r=strtoupper(readline());
      if (!preg_match("@^N(.*)@",$r)) {
        $v=0;
        while (intval($v)==0) {
          echo "Entrez l'écart maximum : ";
          $v=readline();
        }
        $this->findBestSolution($v);
      }
      return;
    }
    if ($this->shortest_l != $this->longest_l) {
      echo substr($this->shortest->getResult()->getText(),1,-1).PHP_EOL;
      echo substr($this->longest->getResult()->getText(),1,-1).PHP_EOL;
    } else {
      echo substr($this->shortest->getResult()->getText(),1,-1).PHP_EOL;
    }
  }
  public function findBestSolution(int $threshold) {
    $this->threshold=$threshold;
    $startNode=$this->getGraph()->getNode("Start","xylian\graph\LcebNode");
    $orig=$threshold;
    $this->explore($startNode);
    $this->displayBestSolution($orig);
  }
  public function displayBestSolution(int $origDelta=0) {
    if ($this->best==NULL) {
      $m=$this->target->getValue()-$origDelta;
      $M=$this->target->getValue()+$origDelta;
      echo "Aucune solution entre $m et $M...".PHP_EOL;
      return;
    }
    if ($this->best!=NULL) {
      echo $this->best->getResult()->getText().PHP_EOL;
    }
  }
  public function start() {
    $startNode=$this->getGraph()->getNode("Start","xylian\graph\LcebNode");
    $this->explore($startNode);
  }

// LCEB main
  public static function initLCEB(int $target,array $numbers, array $opers):LCEB {
    foreach ($numbers as $v) {
      $anumbers[]=new NumberAtom($v, "$v");
    }
    $atarget=new NumberAtom($target, "$target");
    $lceb=new LCEB($atarget, $anumbers, $opers);
    $lceb->start();
    return $lceb;
  }
}

/*****************************************************
******************************************************/
$interactive=(php_sapi_name()=="cli");
if ($interactive) {
  array_shift($argv);
} else {
  foreach ($_REQUEST as $arg => $ignored) {
    $argv[]=preg_split("@_@",$arg);
  }
  $argc=count($argv);
}

if (!is_array($argv) || $argc==1)
  $argv=preg_split("@ @",$argv[0]);

if ($argc<2) {
  die("Mauvais arguments".PHP_EOL);
}

if ($interactive)
  $args=\xylian\args\parseArgs($argv,true);

if ($interactive) {
  $numbers=preg_split("/ /",$args['remaining']);
  $target=intval(array_shift($numbers));
} else {
  $numbers=$argv[1];
  $target=$argv[0][0];
}
$interactive=$args['inter']??(!php_sapi_name());
$interactive=boolval($interactive);
$ajax=false;

if (isset($args['ajax'])) {
  $ajax=true;
} elseif (!$interactive) {
  $ajax=$_REQUEST['ajax']??false;
}

if (isset($args['delta'])) {
  $delta=$args['delta'];
  $interactive=false;
} elseif (!$interactive) {
  $delta=$_REQUEST["delta"]??0;
} else {
  $delta=0;
}

if (isset($args['withPower'])) {
  $opers=\xylian\graph\ops_pow;
} elseif (!$interactive) {
  if (isset($_REQUEST['withPower'])) {
    $opers=\xylian\graph\ops_pow;
  } else {
    $opers=\xylian\graph\ops;
  }
} else {
  $opers=\xylian\graph\ops;
}

foreach ($numbers as &$n) {
  $n=intval($n);
}
if (!$interactive)
  ob_start();
if (count($numbers)>6) {
  echo "Trop de nombres à utiliser.... Maximum 7".PHP_EOL;
  die();
}
$lceb=LCEB::initLCEB($target,$numbers,$opers);
if ($delta==0) {
  $lceb->displayLLSolutions($interactive);
} else {
  $lceb->findBestSolution($delta);
}
//on remplace les \n par des <br /> si pas en CLI et pas ajax
if (php_sapi_name()!="cli") {
  $output=ob_get_contents();
  if (!$ajax) {
    $output=preg_replace("@\n@","<br />",$output);
    $output="<html><body><div style='font:12pt courier'>".$output."</div></body></html>";
  }
  ob_end_clean();
  echo $output;
}

