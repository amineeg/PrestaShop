<?php
/**
 * 2007-2018 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2018 PrestaShop SA
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\PrestaShop\Core\Domain\Customer\ValueObject;

use PrestaShop\PrestaShop\Core\Domain\Customer\Exception\CustomerConstraintException;

/**
 * Stores customer's first name
 */
class FirstName
{
    /**
     * @var string Maximum allowed length for first name
     */
    const MAX_LENGTH = 255;

    /**
     * @var string
     */
    private $firstName;

    /**
     * @param string $firstName
     */
    public function __construct($firstName)
    {
        $this->assertFirstNameDoesNotExceedAllowedLength($firstName);
        $this->assertFirstNameIsValid($firstName);

        $this->firstName = $firstName;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     *
     * @throws CustomerConstraintException
     */
    private function assertFirstNameIsValid($firstName)
    {
        $matchesFirstNamePattern = preg_match('/^[^0-9!<>,;?=+()@#"°{}_$%:¤|]*$/u', stripslashes($firstName));

        if (!$matchesFirstNamePattern) {
            throw new CustomerConstraintException(
                sprintf('Customer first name %s is invalid', var_export($firstName, true)),
                CustomerConstraintException::INVALID_FIRST_NAME
            );
        }
    }

    /**
     * @param string $firstName
     *
     * @throws CustomerConstraintException
     */
    private function assertFirstNameDoesNotExceedAllowedLength($firstName)
    {
        $firstName = html_entity_decode($firstName, ENT_COMPAT, 'UTF-8');

        $length = function_exists('mb_strlen') ? mb_strlen($firstName, 'UTF-8') : strlen($firstName);
        if (self::MAX_LENGTH < $length) {
            throw new CustomerConstraintException(
                sprintf('Customer first name is too long. Max allowed length is %s',self::MAX_LENGTH),
                CustomerConstraintException::INVALID_FIRST_NAME
            );
        }
    }
}
