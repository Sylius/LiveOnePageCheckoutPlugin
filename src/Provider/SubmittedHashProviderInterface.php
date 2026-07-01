<?php

namespace Sylius\LiveOnePageCheckoutPlugin\Provider;

interface SubmittedHashProviderInterface
{
 public function getSubmittedHash(): string;
}