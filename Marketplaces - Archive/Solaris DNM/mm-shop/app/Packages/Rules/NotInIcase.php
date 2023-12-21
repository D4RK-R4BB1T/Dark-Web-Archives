<?php
/**
 * File: NotInIcase.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Packages\Rules;


class NotInIcase
{
    /**
     * Rule name
     * @var string
     */
    protected $rule = 'not_in_icase';

    /**
     * @var array
     */
    protected $values;

    /**
     * Create a new "not in case" rule instance.
     *
     * @param  array  $values
     * @return void
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->rule.':'.implode(',', $this->values);
    }
}