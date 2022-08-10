<?php
namespace xylian\graph;
/*;################################################
;###                                          ###
;##              GRAPH CONSTRUCTOR             ##
;#                                              #
;#                                              #
;##     (c) 2019 MICHAUD Yannick & xylian      ##
;###                                          ###
;################################################*/
class Node {
  private $id;
  protected $name;
  protected $label;
  protected $value;
  protected $previous;
  protected $next;
  static private $maxInstances=0;
  public function __construct(string $name="@",$label="New Node",$value="No Value") {
    $this->name=$name;
    $this->label=$label;
    $this->value=$value;
    $this->previous=array();
    $this->next=array();
    $this->id=++Node::$maxInstances;
  }
  public function __toString():string {
    return $this->name;
  }
  public function getName():string {return $this->name;}
  public function getLabel():string {return $this->label;}
  public function getValue() {return $this->value;}
  public function getPrevious() {return $this->previous;}
  public function getNext() {return $this->next;}
  public function setName($name) {$this->name=$name;}
  public function setLabel($label) {$this->label=$label;}
  public function setValue($value) {$this->value=$value;}
  public function setPrevious($prev) {$this->previous=$prev;}
  public function setNext($next) {$this->next=$next;}
  public function addPrevious($node) {
    $this->previous[]=$node;
    $this->previous=array_unique($this->previous);
    usort($this->previous,array("xylian\graph\Node","compare"));
  }
  public function addNext($node) {
    $this->next[]=$node;
    $this->next=array_unique($this->next);
    usort($this->next,array("xylian\graph\Node","compare"));
  }
  public function removePrevious($node) {
    foreach ($this->getPrevious() as $key=>$prev) {
      if ($prev->getId()==$node->getId())
        unset($this->getPrevious()[$key]);
    }
  }
  public function removeNext($node) {
    foreach ($this->getNext() as $key=>$prev) {
      if ($prev->getId()==$node->getId())
        unset($this->getPrevious()[$key]);
    }
  }
  public function getId():int {return $this->id;}
  public function setId(int $id) {$this->id=$id;}
  static function compare(Node $a, Node $b) {
    return strcmp(strtolower($a->getName()),strtolower($b->getName()));
  }
}

class Graph {
  private $graph;
  public function __construct() {
    $this->graph=array();
  }
  function contains(Node $node):bool {
    $name=$node->getName();
    foreach($this->graph as $node) {
      if ($node->getName()===$name)
        return true;
    }
    return false;
  }
  function getGraph():array {
    return $this->graph;
  }
  function getNode($name,string $className="xylian\graph\Node") {
    foreach($this->graph as $node) {
      if ($node->getName()===$name && get_class($node)==$className)
        return $node;
    }
    return false;
  }
  function addNode(Node $node,bool $unique=true) {
    $this->graph[]=$node;
    if ($unique)
      $this->graph=array_unique($this->graph);
  }
  function linkNodes(Node $prev, Node $next) {
    $prev->addNext($next);
    $next->addPrevious($prev);
  }
  function unLinkNode(Node $node) {
    foreach ($node->getNext() as $next) {
      $next->removePrevious($node);
    }
    foreach ($node->getPrevious() as $prev) {
      $prev->removeNext($node);
    }
    unset($node);
  }
  function removeNodeByName(string $name) {
    foreach($this->graph as $key => $value) {
      if ($value->getName()===$name) {
        foreach ($value->getNext() as $next) {
          $next->removePrevious($value);
        }
        foreach ($value->getPrevious() as $prev) {
          $prev->removeNext($value);
        }
        unset($this->graph[$key]);
        unset($value);
      }
    }
  }

}

