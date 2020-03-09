<?php

namespace Packetery\Checkout\Block\System\Config\Form;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\Phrase;

/**
 * Class Active
 *
 * @package VendorName\SysConfigTable\Block\System\Config\Form\Field
 */
class Rules extends \Magento\Config\Model\Config\Backend\Serialized\ArraySerialized
{

    const CONFIGURATION_KEY = 'rules_';
    const MAX_WEIGHT_GLOBAL_DATA_PATH = 'groups/rules_global/fields/max_weight';

    public function beforeSave()
    {
        // weigth could be changed, cannot use directly from config
        $maxWeightGlobal = intval($this->getData(self::MAX_WEIGHT_GLOBAL_DATA_PATH, 'value'));

        $value = $this->getValue();
        // if index name is __empty, nothing to save or validate
        if (!is_array($value) || (count($value) === 1 && isset($value['__empty'])))
        {
            return parent::beforeSave();
        }

        $ruleLangCode = self::getRuleLangCode($this->getPath());
        $ruleLangCodeMessage = $ruleLangCode ? " [{$ruleLangCode}]" : "";

        $ranges = [];

        foreach ($value as $key => $line)
        {
            if ($key === "__empty")
            {
                continue;
            }

            $rangeMessage = "{$line['from']} - {$line['to']}";

            if ($line['from'] >= $line['to'])
            {
                $message = __('Weight from must be less than Weight to (includes)') . ' ' . $ruleLangCodeMessage;
                    throw new \Magento\Framework\Exception\ValidatorException(new Phrase($message));
            }

            if (!self::checkRanges($ranges, $line['from'], $line['to']))
            {
                $ruleLangCodePattern = $ruleLangCode ? "[{$ruleLangCode}]" : "";
                $message = __('The weight intervals must not overlap:') . ' ' . $rangeMessage . ' ' . $ruleLangCodePattern;
                throw new \Magento\Framework\Exception\ValidatorException(new Phrase($message));
            }

            // intervals are added to array for further validation
            $ranges[] = [
                'from' => $line['from'],
                'to'   => $line['to'],
            ];

            if (empty($line['price']))
            {
                $message = __('Some prices are not filled') . ' ' . $ruleLangCodeMessage;
                throw new \Magento\Framework\Exception\ValidatorException(new Phrase($message));
            }

            if ($line['to'] > $maxWeightGlobal)
            {

                $message = __('The weight interval exceeds the global maximum weight:') . ' ' .  $rangeMessage . ' ' . $ruleLangCodeMessage;
                throw new \Magento\Framework\Exception\ValidatorException(new Phrase($message));
            }

            $temp[$key] = $line;
        }

        return parent::beforeSave();
    }

    /**
     * Is number in range?
     *
     * @param int   $value Value, which is checked in range.
     * @param array $range Range - must contains [from] and [to] indexes.
     *
     * @return bool
     */
    private static function isInRange($value, array $range)
    {
        if (!isset($range['from']) || !isset($range['to']))
        {
            return FALSE;
        }

        // [to] weight is included
        return ($range['from'] <= $value) && ($value < $range['to']);
    }

    /**
     * Check overlapping of ranges
     *
     * @param array $ranges Ranges, which will be checked.
     * @param int   $from   Range - from.
     * @param int   $to     Range - to.
     *
     * @return bool
     */
    private static function checkRanges($ranges, $from, $to)
    {
        if (empty($ranges))
        {
            return TRUE;
        }

        foreach ($ranges as $range)
        {
            // Numbers are not in range
            if (!self::isInRange($from, $range) && !self::isInRange($to, $range))
            {
                continue;
            }

            return FALSE;
        }

        return TRUE;
    }

    /**
     * Get language code from config.
     *
     * @param string $path
     *
     * @return string
     */
    private static function getRuleLangCode($path)
    {
        // contains for example: "packetery_rules/rules_cz/rules"
        $configParts = explode('/', $path);
        if (!isset($configParts[1]))
        {
            return '';
        }

        // if we get "rules_cz" - only "cz" is needed
        return strtoupper(str_replace(self::CONFIGURATION_KEY, '', $configParts[1]));
    }
}
