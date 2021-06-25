[Návod v češtině](https://github.com/Zasilkovna/magento2#modul-pro-magento-2)
    
# Module for Magento 2

### Download module

[Current version 2.1.0](https://github.com/Zasilkovna/magento2/archive/v2.1.0.zip)

### Installation

Installation and registration of the module are done by CLI utility, which is part of Magento 2.
This utility is available in Magento installation directory as "/bin/magento".

- copy directory 'Packetery' to directory: `/app/code`
- enable module using CLI utility: `bin/magento module:enable Packetery_Checkout --clear-static-content`
- registration of module: `bin/magento setup:upgrade`
- re-deploy static content (not needed in dev mode): `bin/magento setup:static-content:deploy`
- project recompiling: `bin/magento setup:di:compile`
- clean cache: `bin/magento cache:clean`
- set Packeta configuration in administration for default scope even if Packeta carrier is inactive
- import carriers: `bin/magento packetery:import-feed-carriers`
- set or update call of `bin/magento cron:run`, in such way Packeta cron jobs can be processed

## Upgrading

- set Packeta configuration in administration for default scope even if Packeta carrier is inactive
- enable maintenance mode
- remove all previous source files (remove app/code/Packetery folder)
- next steps are the same as during the installation
- (optional) migrate configuration from 2.0.1 and 2.0.2 structure: `bin/magento packetery:migrate-price-rules`
- (optional) migrate global price from structure of versions up to 2.0.5: `bin/magento packetery:migrate-default-price`
- disable maintenance mode
- check configuration

### Configuration and "How to" guide

### Information about the module

#### Supported languages:

- czech
- english

#### Supported versions:

- Magento 2.3, 2.4
- If you have a problem using the module, please contact us by email: [support@packeta.com](mailto:support@packeta.com)

#### Supported features:

- integration of widget v6 in the cart
- support for external carriers' pickup points
- delivery to an address via external carriers
- setting different prices for each carrier
- free shipping from the specified price
- export shipments to a CSV file that can be imported in [client section](https://client.packeta.com/)
- possibility to change the pickup point for an existing order in the administration

#### Restrictions:

- currently, the module does not support: delivery to non-EU addresses, carriers who have prohibited cash on delivery,
  evening delivery Prague, Brno, Ostrava, Bratislava

# Modul pro Magento 2

### Stažení modulu

[Aktuální verze 2.1.0](https://github.com/Zasilkovna/magento2/archive/v2.1.0.zip)

### Instalace

Instalace a registrace modulu se provádí CLI utilitou, která je součástí Magento 2. Tato utitita je dostupná v instalačním adresáři Magenta jako "/bin/magento".

- nakopírovat adresář 'Packetery' do adresáře: `/app/code`
- povolení modulu pomocí CLI utility: `bin/magento module:enable Packetery_Checkout --clear-static-content`
- registrace modulu: `bin/magento setup:upgrade`
- re-deploy statického obsahu (není potřeba v dev módu): `bin/magento setup:static-content:deploy`
- rekompilace projektu: `bin/magento setup:di:compile`
- smazání cache: `bin/magento cache:clean`
- nastavte v administraci přepravce Zásilkovna pro výchozí kontext (scope), ikdyž je přepravce neaktivní
- nahrajte přepravce: `bin/magento packetery:import-feed-carriers`
- nastavte či upravte volání `bin/magento cron:run`, tak aby se Zásilkovní úlohy dokázaly zpracovat

### Aktualizace modulu

- nastavte v administraci přepravce Zásilkovna pro výchozí kontext (scope), ikdyž je přepravce neaktivní
- zapnout režim údržby
- smazat zdrojové soubory (smazat složku app/code/Packetery)
- další postup stejný jako při instalaci
- (nepovinné) přemigrujte konfiguraci ze struktury verze 2.0.1 a 2.0.2: `bin/magento packetery:migrate-price-rules`
- (nepovinné) přemigrujte globální cenu ze struktury verze až 2.0.5: `bin/magento packetery:migrate-default-price`
- vypnout režim údržby
- zkontrolovat konfiguraci

### Konfigurace a návod k použití

### Informace o modulu

#### Podporované jazyky:

- čeština
- angličtina

#### Podporované verze:

- Magento 2.3, 2.4
- Při problému s použitím modulu nás kontaktujte na emailu: [technicka.podpora@zasilkovna.cz](mailto:technicka.podpora@zasilkovna.cz)

#### Poskytované funkce:

- integrace widgetu v6 v košíku eshopu
- podpora výdejních míst externích dopravců
- doručení na adresu přes externí dopravce Zásilkovny
- nastavení různé ceny pro jednotlivé dopravce
- doprava zdarma od zadané ceny
- export zásilek do csv souboru, který lze importovat v [klientské sekci](https://client.packeta.com/)
- možnost změny výdejního místa u existující objednávky v administraci

#### Omezení:

- v současné době modul nepodporuje: doručení na adresu mimo EU, dopravce kteří mají zakázanou dobírku, večerní doručení Praha, Brno, Ostrava, Bratislava
