<?php
namespace xylian\graph;
/*################################################
;###                                          ###
;##                LCEB GRAPH                  ##
;#                    v1.5                      #
;#                                              #
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

function removeNumberAt(array $arr,int $index) {
  $l=count($arr);
  $result=array();
  for ($i=0;$i<$l;$i++) {
    if ($i!=$index)
      $result[]=$arr[$i];
  }
  return $result;
}
function removeFirstNumber(array $arr,$number) {
  $result=array();
  $removed=false;
  foreach ($arr as $value) {
    if ($value!=$number || $removed) {
      $result[]=$value;
    } else {
      $removed=true;
    }
  }
  return $result;
}

class LcebNode extends Node {
  public function __construct(string $name="noName",string $label="",$value="") {
    parent::__construct($name,$label,is_array($value)?$value:array(0,0,0,0,0,0,array()));
    if ($name=="")
      $this->setName("LCEB ".$this->getId());
    if ($label=="")
      $this->setLabel("LCEB ".$this->getId());
  }
// CORE
  public function getNumber() {
    return $this->value[0];
  }
  public function getOperation() {
    return $this->value[1];
  }
  public function getOperande() {
    return $this->value[2];
  }
  public function getResult() {
    return $this->value[3];
  }
  public function getTarget() {
    return $this->value[4];
  }
  public function getRemaining() {
    return $this->value[5];
  }

  public function display():string {
    return $this->getNumber()." ".$this->getOperation()." ".$this->getOperande()." = ".$this->getResult();
  }

  public function displayOnlyOp():string {
    return $this->getNumber()." ".$this->getOperation()." ".$this->getOperande();
  }

  public function setNumber ($value=0) {
    $this->value[0]=$value;
  }
  public function setOperation($value=0) {
    $this->value[1]=$value;
  }
  public function setOperande($value=0) {
    $this->value[2]=$value;
  }
  public function setResult($value=0) {
    $this->value[3]=$value;
  }
  public function setTarget($value=0) {
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
      if ($prev!==NULL) {
        $result=$prev->displayPrevious();
      }
      $tmp=$this->display().PHP_EOL;
      $result=$result.$tmp;
      return $result;
    }
  }
}

function add ($a, int $b) {
  if ($a!=0 && $b!=0) {
    return $a+$b;
  }
  return false;
}
function sub ($a, $b) {
  if (($a-$b)>=0) {
    return $a-$b;
  }
  return false;
}
function mul ($a, $b) {
  if ($a>1 && $b>1) {
    return $a*$b;
  }
  return false;
}
function div ($a, $b) {
  if ($b==0) return false;
  if ($a!=1 && $b!=1) {
    $tmp=$a/$b;
    if ((intval($tmp)*$b) == $a) {
      return $tmp;
    }
  }
  return false;
}
function pow ($a, $b) {
  if ($a!=1 && $b!=1) {
    $tmp=intval(\pow($a,$b));
    if ($tmp==\pow($a,$b))
      return $tmp;
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

  public function __construct(int $target, array $numbers, array $opers=\xylian\graph\ops) {
    $graph=new Graph();
    arsort($numbers);
    $start=new LcebNode("Start","Début",array(0,0,0,0,$target,$numbers));
    $graph->addNode($start);
    $this->operations=$opers;
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
    if (abs($node->getResult()-$this->target)>$this->threshold) {
      return;
    } else {
      if ($this->best != NULL) {
        $this->getGraph()->unLinkNode($this->best);
        unset($this->best);
      }
      $this->best=$node;
    }
  }
  public function getGraph() {
    return $this->graph;
  }
  public function getTarget() {
    return $this->target;
  }
  public function getInitNumbers() {
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
    if ($v==$target) {
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
      if ($this->threshold>0 && (abs($v - $target) <= $this->threshold)) {
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
  private function coreDisplaySolutions(\xylian\graph\LcebNode $node) {
    if ($node->getResult()==$this->getTarget()) {
      echo "----------\nSolution :".PHP_EOL;
      echo $node->displayPrevious();
    } else {
      foreach ($node->getNext() as $nextNode) {
        $this->coreDisplaySolutions($nextNode);
      }
    }
  }
  public function displayAllSolutions() {
    if (!$this->isSolved()) {
      echo "Pas de solution....".PHP_EOL;
      return;
    }
    $start=$this->getGraph()->getNode("Start","xylian\graph\LcebNode");
    $this->coreDisplaySolutions($start);
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
      echo "--------".PHP_EOL;
      echo "+ court (".($this->shortest_l).")".PHP_EOL;
      echo "--------".PHP_EOL;
      echo $this->shortest->displayPrevious();
      echo "-------".PHP_EOL;
      echo "+ long (".($this->longest_l).")".PHP_EOL;
      echo "-------".PHP_EOL;
      echo $this->longest->displayPrevious();
    } else {
      echo "---------".PHP_EOL;
      echo "meilleur (".($this->shortest_l).")".PHP_EOL;
      echo "---------".PHP_EOL;
      echo $this->shortest->displayPrevious();
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
      $m=$this->target-$origDelta;
      $M=$this->target+$origDelta;
      echo "Aucune solution entre $m et $M...".PHP_EOL;
      return;
    }
    if ($this->best!=NULL) {
      if ($this->threshold != 0)
        echo "------ PLUS PROCHE ------".PHP_EOL;
      else
        echo "-------- SOLUTION -------".PHP_EOL;
      echo $this->best->displayPrevious();
    }
  }
  public function start() {
    $startNode=$this->getGraph()->getNode("Start","xylian\graph\LcebNode");
    $this->explore($startNode);
  }

// LCEB main
  public static function initLCEB(int $target=0, array $numbers=array(1,1,1,1,1,1), array $opers):LCEB {
    $lceb=new LCEB($target, $numbers, $opers);
    $lceb->start();
    return $lceb;
  }
}
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
if (!$interactive)
  ob_start();

if (isset($args['ajax'])) {
  $ajax=true;
} elseif (!$interactive) {
  $ajax=$_REQUEST['ajax']??false;
}

if (isset($args['delta'])) {
  $delta=$args['delta'];
  $delta=($delta===false)?0:$delta;
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
if (count($numbers)>6) {
  echo "Trop de nombres à utiliser.... Maximum 6".PHP_EOL;
  die();
}
$lceb=LCEB::initLCEB($target, $numbers, $opers);
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
