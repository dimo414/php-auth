<?php

// Class represents a bitmask, and can convert an array into a bitmask, or a bitmask into an array,
// and allows you to get and set the values of any index of the bitmap, by numerical or associative index

class bitmasker{
  protected $size;
  protected $arr = array();
  protected $revArr = array();
  
  public $all;
  public $none;
  public $allInt;
  public $noneInt = 0;
  
  /*
  Takes either an integer size or an array.  An integer will specify how many fields this mask will have,
  an array will assign names to each field in the mask.
  
  For efficient storage you can get an integer representation of the mask, however this can potentially become
  imprecise if you have more fields than your platform allows.  Generally, 32 bit platforms can handle up to
  31 fields, and 64 bit platforms can handle up to 63 fields.
  */
  function __construct($sizeOrArray) {
    if(is_int($sizeOrArray)){
      $this->size = (int)$sizeOrArray;
      for($i = 0; $i < $this->size; $i++){
        $this->arr[] = $i;
        $this->revArr[$i] = $i;
      }
    } elseif(is_array($sizeOrArray)){
      $size = 0;
      foreach($sizeOrArray as $arr){
        $this->arr[] = $arr;
        $this->revArr[$arr] = $size;
        $size++;
      }
      $this->size = $size;
    }
    
    $this->all = str_pad('',$this->size,'1');
    $this->none = str_pad('',$this->size,'0');
    $this->allInt = bindec($this->all);
  }
   
  // takes an array of indices (numerical or associative, depending on how the bitmasker was constructed) and returns a
  // string bitmask.  Any values in the array that are outside the set of possible values are ignored.
  function arrayToMask($arr){
    $mask = '';
    for($i = 0; $i < $this->size; $i++){
      if(in_array($this->arr[$i],$arr)){
        $mask = '1'.$mask;
      } else {
        $mask = '0'.$mask;
      }
    }
    return $mask;
  }
  
  // converts an array of values to an int mask
  function arrayToIntMask($arr){
    return $this->maskToIntMask($this->arrayToMask($arr));
  }
  
  // takes a bitmask and returns an array of numerical or associative values
  function maskToArray($mask){
    if(is_int($mask))
      $mask = $this->intMaskToMask($mask);
    $retArr = array();
    $array = str_split($mask);
    foreach($array as $indx => $char) {
      if($char == '1'){
        $retArr[] = $this->arr[$this->size - $indx - 1];
      }
    }
    return $retArr;
  }
  
  // returns the value of $mask at $index
  function getValue($mask,$index){
    if(is_int($mask))
      $mask = $this->intMaskToMask($mask);
    $arr = str_split($mask);
    return $arr[$this->size - $this->revArr[$index] - 1] == '1';
  }
  
  // returns a new mask of $mask with the value at $index set to $val
  function setValue($mask,$index,$val){
    $int = false;
    if(is_int($mask)){
      $mask = $this->intMaskToMask($mask);
      $int = true;
    }
    $ind = $this->size - $this->revArr[$index] - 1;
    $mask = substr($mask,0,$ind).($val ? '1' : '0').substr($mask,$ind+1);
    if($int){
      $mask = $this->maskToIntMask($mask);
    }
    return $mask;
  }
  
  // returns the integer union of two masks
  function unionInt($maskA, $maskB){
    if(!is_int($maskA))
      $maskA = $this->maskToIntMask($maskA);
    if(!is_int($maskB))
      $maskB = $this->maskToIntMask($maskB);
    return $maskA & $maskB;
  }
  
  // returns the union of two masks.  If safeIntMask() is true, uses binary arithmatic, otherwise uses array-intersection
  function union($maskA, $maskB){
    if($this->safeIntMask()){
      return $this->intMaskToMask($this->unionInt($maskA, $maskB));
    }
    return $this->arrayToMask(array_intersect($this->maskToArray($maskA),$this->maskToArray($maskB)));
  }
  
  // Indicates that it is safe to generate integer bitmasks, as a function of the number of fields.
  // If this returns false, storing masks as integers is not recommended.
  function safeIntMask(){
    return PHP_INT_SIZE*8-1 >= $this->size;
  }
  
  // converts a binary mask to an integer
  function maskToIntMask($mask){
    return bindec($mask);
  }
  
  // converts an int mask to a binary mask
  function intMaskToMask($mask){
      return str_pad(decbin($mask),$this->size,'0',STR_PAD_LEFT);
  }
  
  function getFields(){
    return $this->arr;
  }
  
  function getSize(){
    return $this->size;
  }
}

?>