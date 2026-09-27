<?php
/**
 * 2007-2026 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2026 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ShippingVillage extends ObjectModel
{
    /** @var int */
    public $id_shipping_village;

    /** @var string */
    public $name;

    /** @var string|null */
    public $zipcode;

    /** @var float */
    public $price = 0.0;

    /** @var bool */
    public $active = true;

    /** @var int */
    public $position = 0;

    /** @var string */
    public $date_add;

    /** @var string */
    public $date_upd;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'shipping_village',
        'primary' => 'id_shipping_village',
        'fields' => [
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 128],
            'zipcode' => ['type' => self::TYPE_STRING, 'validate' => 'isPostCode', 'required' => false, 'size' => 32],
            'price' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => false],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true],
            'position' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => false],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    /**
     * Get active villages ordered by position and name
     *
     * @return array
     */
    public static function getActiveVillages(): array
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('shipping_village');
        $sql->where('`active` = 1');
        $sql->orderBy('`position` ASC, `name` ASC');

        $result = Db::getInstance()->executeS($sql);

        return is_array($result) ? $result : [];
    }

    /**
     * Get active village by name (case-insensitive)
     *
     * @param string $cityName
     * @return array|null
     */
    public static function getVillageByName(string $cityName): ?array
    {
        $cleanCity = trim(mb_strtolower($cityName, 'UTF-8'));
        if ($cleanCity === '') {
            return null;
        }

        $villages = self::getActiveVillages();
        foreach ($villages as $village) {
            $villageName = trim(mb_strtolower($village['name'], 'UTF-8'));
            if ($villageName === $cleanCity) {
                return $village;
            }
        }

        return null;
    }

    /**
     * Check if a given city name matches an active allowed village
     *
     * @param string $cityName
     * @return bool
     */
    public static function isAllowedVillage(string $cityName): bool
    {
        $cleanCity = trim(mb_strtolower($cityName, 'UTF-8'));
        if ($cleanCity === '') {
            return false;
        }

        $villages = self::getActiveVillages();
        foreach ($villages as $village) {
            $villageName = trim(mb_strtolower($village['name'], 'UTF-8'));
            if ($villageName === $cleanCity) {
                return true;
            }
        }

        return false;
    }
}
