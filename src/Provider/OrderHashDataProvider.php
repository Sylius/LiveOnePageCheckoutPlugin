<?php

namespace Sylius\LiveOnePageCheckoutPlugin\Provider;

use Sylius\Component\Core\Model\OrderInterface;

final readonly class OrderHashDataProvider implements OrderHashDataProviderInterface
{
    public function getData(OrderInterface $order): array
    {
        return  [
            'total' => $order->getTotal(),
            'promotions' => $this->getPromotionCodes($order),
            'shipments' => $this->getShippingMethodCodes($order),
            'payments' => $this->getPaymentMethodCodes($order),
        ];
    }

    private function getPromotionCodes(OrderInterface $order): array
    {
        $codes = [];
        foreach ($order->getPromotions() as $promotion) {
            $codes[] = $promotion->getCode();
        }
        sort($codes);

        return $codes;
    }

    private function getShippingMethodCodes(OrderInterface $order): array
    {
        $codes = [];
        foreach ($order->getShipments() as $shipment) {
            $codes[] = $shipment->getMethod()?->getCode();
        }
        sort($codes);

        return $codes;
    }

    private function getPaymentMethodCodes(OrderInterface $order): array
    {
        $codes = [];
        foreach ($order->getPayments() as $payment) {
            $codes[] = $payment->getMethod()?->getCode();
        }
        sort($codes);

        return $codes;
    }
}