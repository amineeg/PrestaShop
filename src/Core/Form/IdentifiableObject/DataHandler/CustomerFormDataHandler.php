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

namespace PrestaShop\PrestaShop\Core\Form\IdentifiableObject\DataHandler;

use DateTime;
use DateTimeImmutable;
use PrestaShop\PrestaShop\Core\CommandBus\CommandBusInterface;
use PrestaShop\PrestaShop\Core\Domain\Customer\Command\AddCustomerCommand;
use PrestaShop\PrestaShop\Core\Domain\Customer\Command\EditCustomerCommand;
use PrestaShop\PrestaShop\Core\Domain\Customer\ValueObject\CustomerId;
use PrestaShop\PrestaShop\Core\Domain\Customer\ValueObject\Email;
use PrestaShop\PrestaShop\Core\Domain\Customer\ValueObject\FirstName;
use PrestaShop\PrestaShop\Core\Domain\Customer\ValueObject\LastName;

/**
 * Saves or updates customer data submitted in form
 */
final class CustomerFormDataHandler implements FormDataHandlerInterface
{
    /**
     * @var CommandBusInterface
     */
    private $bus;

    /**
     * @var int
     */
    private $contextShopId;

    /**
     * @var bool
     */
    private $isB2bFeatureEnabled;

    /**
     * @param CommandBusInterface $bus
     * @param int $contextShopId
     * @param bool $isB2bFeatureEnabled
     */
    public function __construct(
        CommandBusInterface $bus,
        $contextShopId,
        $isB2bFeatureEnabled
    ) {
        $this->bus = $bus;
        $this->contextShopId = $contextShopId;
        $this->isB2bFeatureEnabled = $isB2bFeatureEnabled;
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data)
    {
        $command = $this->buildCustomerAddCommandFromFormData($data);

        /** @var CustomerId $customerId */
        $customerId = $this->bus->handle($command);

        return $customerId->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function update($customerId, array $data)
    {
        $command = $this->buildCustomerEditCommand($customerId, $data);

        /** @var CustomerId $customerId */
        $customerId = $this->bus->handle($command);

        return $customerId->getValue();
    }

    /**
     * @param array $data
     *
     * @return AddCustomerCommand
     */
    private function buildCustomerAddCommandFromFormData(array $data)
    {
        $command = new AddCustomerCommand(
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['password'],
            (int) $data['default_group_id'],
            array_map(function ($groupId) { return (int) $groupId; }, $data['group_ids']),
            $this->contextShopId,
            (int) $data['gender_id'],
            (bool) $data['is_enabled'],
            (bool) $data['is_partner_offers_subscribed']
        );

        if (!$this->isB2bFeatureEnabled) {
            return $command;
        }

        $command
            ->setCompanyName($data['company_name'])
            ->setSiretCode($data['siret_code'])
            ->setApeCode($data['ape_code'])
            ->setWebsite($data['website'])
            ->setAllowedOutstandingAmount($data['allowed_outstanding_amount'])
            ->setMaxPaymentDays($data['max_payment_days'])
            ->setRiskId($data['risk_id'])
        ;

        return $command;
    }

    /**
     * @param int $customerId
     * @param array $data
     *
     * @return EditCustomerCommand
     */
    private function buildCustomerEditCommand($customerId, array $data)
    {
        $command = new EditCustomerCommand(new CustomerId($customerId));
        $command
            ->setEmail(new Email($data['email']))
            ->setFirstName(new FirstName($data['first_name']))
            ->setLastName(new LastName($data['last_name']))
            ->setIsEnabled($data['is_enabled'])
            ->setIsPartnerOffersSubscribed($data['is_partner_offers_subscribed'])
            ->setDefaultGroupId((int) $data['default_group_id'])
            ->setCompanyName(null !== $data['company_name'] ? $data['company_name'] : '')
            ->setSiretCode(null !== $data['siret_code'] ? $data['siret_code'] : '')
            ->setApeCode(null !== $data['ape_code'] ? $data['ape_code'] : '')
            ->setWebsite(null !== $data['website'] ? $data['website'] : '')
            ->setAllowedOutstandingAmount(
                null !== $data['allowed_outstanding_amount'] ? (float) $data['allowed_outstanding_amount'] : null
            )
            ->setMaxPaymentDays(null !== $data['max_payment_days'])
            ->setRiskId($data['risk_id'])
        ;

        if (null !== $data['group_ids']) {
            $command->setGroupIds(
                array_map(function ($groupId) { return (int) $groupId; }, $data['group_ids'])
            );
        }

        if (null !== $data['gender_id']) {
            $command->setGenderId($data['gender_id']);
        }

        if ($data['birthday'] instanceof DateTime) {
            $command->setBirthday(DateTimeImmutable::createFromMutable($data['birthday']));
        }

        return $command;
    }
}
