#!/usr/bin/env bash

set -e

if [[ "${1}" == "6.4.7.0" ]] || [[ "${1}" == "6.4.6.1" ]] || [[ "${1}" == "6.4.5.1" ]] || [[ "${1}" == "6.4.4.1" ]] || [[ "${1}" == "6.4.3.1" ]]; then
  echo "Shopware version ${1}"
else
  exit 0
fi

docker cp shop:/var/www/html/config/bundles.php $(pwd)/bundles.php
if [[ "${1}" == "6.4.7.0" ]] || [[ "${1}" == "6.4.6.1" ]]; then
  sed -i'' -e 's/return $bundles;/if (\\class_exists(Shopware\\Core\\DevOps\\DevOps::class)) {$bundles[Shopware\\Core\\DevOps\\DevOps::class] = ["all" => true];} return $bundles;/g' bundles.php
elif [[ "${1}" == "6.4.5.1" ]] || [[ "${1}" == "6.4.4.1" ]] || [[ "${1}" == "6.4.3.1" ]]; then
  sed -i'' -e 's/];/Shopware\\Core\\DevOps\\DevOps::class => ["all" => true]];/g' bundles.php
fi
docker cp $(pwd)/bundles.php shop:/var/www/html/config/bundles.php
rm -f bundles.php bundles.php-e

docker cp shop:/var/www/html/vendor/shopware/core/Checkout/Payment/PaymentMethodDefinition.php $(pwd)/PaymentMethodDefinition.php
sed -i'' -e 's/->removeFlag(ApiAware::class)//g' PaymentMethodDefinition.php
docker cp $(pwd)/PaymentMethodDefinition.php shop:/var/www/html/vendor/shopware/core/Checkout/Payment/PaymentMethodDefinition.php
rm -f PaymentMethodDefinition.php PaymentMethodDefinition.php-e
