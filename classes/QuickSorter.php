<?php
/**
 * Sorter class
 *
 * Sort elements with quicksort algorithm.
 * This is an example of the algorithm, PHP uses
 * to sort elements in lists. This function is
 * obsolet to use and it should only used as example
 * for better understanding what happens.
 */
class Sorter
{
    /**
     * variable to hold the list to sort
     */
    protected $list;
    /**
     * set list variablie
     * 
     * @param array $list
     * @return void
     */
    public function setList($list) {
        $this->list = $list;
    }
    /**
     * get list variablie
     * 
     * @return array list variable
     */
    public function getList() {
        return $this->list;
    }
    /**
     * sort function
     * 
     * @param array $list List to sort
     * @return array Sorted list
     */
    public function sort($list) {
        $this->setList($list);
        $this->quicksort(0,(count($list)-1));
        return $this->getList();
    }
    /**
     * quicksort function
     *
     * this function uses the split function to
     * sort the list by the pivot element
     * 
     * @param int $left Left index
     * @param int $right Right index
     * @return void
     */
    private function quicksort($left, $right)
    {
        if($left < $right) {
            // first we use split to semi-sort and get the pivot index
            $splitter = $this->split($left, $right);
            // next we sort left part and right part, seperated by the pivot index
            $this->quicksort($left, $splitter-1);
            $this->quicksort($splitter+1, $right);
        }
    }
    /**
     * split function
     *
     * use this function to split list in two lists
     * seperated by the pivot element and semi-sorted
     * 
     * @param int $left Left index
     * @param int $right Right index
     * @return int New pivot index
     */
    private function split($left, $right)
    {
        $i = $left;
        $j = $right-1;
        $pivot = $this->list[$right]; // how to get the list???
        while($i < $j) {
            // search left elements for one that is bigger then pivot
            while($this->list[$i] <= $pivot && $i < $right) {
                $i++;
            }
            // search right elements for one that is smaller then pivot
            while($this->list[$j] >= $pivot && $j > $left) {
                $j--;
            }
            // if i is smaller then j, switch elements
            if($i < $j) {
                $tmpi = $this->list[$i];
                $tmpj = $this->list[$j];
                $this->list[$i] = $tmpj;
                $this->list[$j] = $tmpi;
            }
        }
        // if i element is bigger then pivot, switch elements
        if($this->list[$i] > $pivot) {
            $tmpp = $this->list[$right];
            $tmpi = $this->list[$i];
            $this->list[$right] = $tmpi;
            $this->list[$i] = $tmpp;
        }
        return $i;
    }
}


// call example
$Sorter = new Sorter();
//$array = str_split('Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut l',1);
$array = str_split('ansimpleexample',1);
echo "unsorted:\r\n";
foreach($array as $a1) {
    echo $a1." ";
}
echo "\r\n";
$array = $Sorter->sort($array);

echo "sorted:\r\n";
foreach($array as $a2) {
    echo $a2." ";
}
echo "\r\n";