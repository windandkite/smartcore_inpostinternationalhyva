<?php

declare(strict_types=1);

namespace Smartcore\InPostInternationalHyva\Plugin;

use Hyva\Checkout\ViewModel\Checkout\ShippingSummary;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\Data\AddressInterface;
use Smartcore\InPostInternational\Model\ConfigProvider;
use Smartcore\InPostInternational\Service\PickupPointAddressService;

/**
 * Show the selected pickup point as the "Ship To" address when it will be used as the order shipping address
 */
class ShowPickupPointInShippingSummary
{
    /**
     * @param CheckoutSession $checkoutSession
     * @param ConfigProvider $configProvider
     * @param PickupPointAddressService $pickupPointAddressService
     */
    public function __construct(
        private readonly CheckoutSession $checkoutSession,
        private readonly ConfigProvider $configProvider,
        private readonly PickupPointAddressService $pickupPointAddressService,
    ) {
    }

    /**
     * Replace the summary address with the pickup point, keeping the customer's name and phone number
     *
     * Uses the pickup point details saved from the geowidget, so the summary doesn't call the Points API.
     *
     * @param ShippingSummary $subject
     * @param AddressInterface|null $result
     * @return AddressInterface|null
     */
    public function afterGetShippingAddress(ShippingSummary $subject, ?AddressInterface $result): ?AddressInterface
    {
        if ($result === null || !$this->configProvider->isPointAsShippingAddressEnabled()) {
            return $result;
        }

        $pointAddress = $this->pickupPointAddressService->getAddress($this->checkoutSession->getQuote(), false);

        if ($pointAddress === null) {
            return $result;
        }

        return $result->setCompany($pointAddress['company'])
            ->setStreet([$pointAddress['street']])
            ->setCity($pointAddress['city'])
            ->setPostcode($pointAddress['postcode'])
            ->setRegion(null)
            ->setRegionId(null)
            ->setCountryId($pointAddress['country_id']);
    }
}
