<?php

namespace RapidWeb\Search;

trait SearchExtendConditions
{
    /**
     * Add to sanityCheck, to split operators from fieldname
     * @param $id
     */
    public function setFields() {
        foreach($this->conditions as $fieldName => $value) {
            // Undo fieldname from operators
            $fieldName = $this->splitOperator($fieldName);
            // No doubles
            if (!in_array($fieldName, $this->fields)) {
                $this->fields[] = $fieldName;
            }
        }
    }

    /**
     * Walk true conditions for each dataRow
     * @param $dataRow
     * @return bool
     */
    public function setDataItems(&$dataRow):bool {
        $methods = get_class_methods($this);
        $methods = array_filter($methods, function($method) {
           return preg_match("/^__.*Filter$/",$method);
        });

        $result = true;
        foreach($this->conditions as $fieldName => $value) {
            // Split fieldname and operator
            list($fieldName, $operator) = $this->splitOperator($fieldName, true);
            $dataItem = $dataRow->getDataItemByFieldName($fieldName);

            foreach ($methods as $filterMethod) {
                if(method_exists($this, $filterMethod)) {
                    $result = $this->$filterMethod($dataItem, $operator, $value) ? $result : false;
                }
            }
        }
        return $result;
    }

    /**
     * When there is an operator in the Fieldname, split
     * return only fieldname or both
     *
     * @param string $fieldName
     * @param bool $return
     * @return array|string
     */
    private function splitOperator(string $fieldName, bool $return = false): array | string {
        $operator = "==";
        if(preg_match("/:/",$fieldName)) {
            // split fieldname and operator
            list($fieldName, $operator) = preg_split("/:/", $fieldName);
        }

        if(!$return) return $fieldName;
        return [$fieldName,$operator];
    }

    /**
     * match emailadres
     * @param \RapidWeb\uxdm\Objects\DataItem $dataItem
     * @param string $operator
     * @param mixed $value
     * @return bool
     */
    private function __emailFilter(\RapidWeb\uxdm\Objects\DataItem $dataItem, string $operator, mixed $value):bool {
        $result = true;
        if(preg_match("/email/", $operator)) {
            if ($operator == '!email' && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $result = false;
            } elseif ($operator == 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $result = false;
            }
        }
        return $result;
    }

    /**
     * in or not in list
     * @param \RapidWeb\uxdm\Objects\DataItem $dataItem
     * @param string $operator
     * @param mixed $value
     * @return bool
     */
    private function __inFilter(\RapidWeb\uxdm\Objects\DataItem $dataItem, string $operator, mixed $value):bool {
        $result = true;
        if(preg_match("/in/", $operator)) {
            if ($operator == '!in' && in_array($dataItem->value, explode(",", $value))) {
                $result = false;
            } elseif ($operator == 'in' && !in_array($dataItem->value, explode(",", $value))) {
                $result = false;
            }
        }
        return $result;
    }

    /**
     * value compare
     * @param \RapidWeb\uxdm\Objects\DataItem $dataItem
     * @param string $operator
     * @param mixed $value
     * @return bool
     */
    private function __comparisonFilter(\RapidWeb\uxdm\Objects\DataItem $dataItem, string $operator, mixed $value):bool {
        if(preg_match("/(<|>|==|\!=|<=|>=)/", $operator)) {
            return eval('return (' . $dataItem->value . $operator . $value . ');');
        }
        return true;
    }
}