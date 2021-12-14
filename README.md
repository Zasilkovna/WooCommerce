[Návod v češtině](https://github.com/Zasilkovna/magento2#modul-pro-magento-2)
    
# Module for Magento 2

### Download module

[Current version 2.2.0](https://github.com/Zasilkovna/magento2/archive/v2.2.0.zip)

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
- set up command `bin/magento cron:run` in cron, in order to update carriers regularly

## Upgrading

- set Packeta configuration in administration for default scope even if Packeta carrier is inactive
- enable maintenance mode: `bin/magento maintenance:enable`
- remove all previous source files (remove app/code/Packetery folder)
- next steps are the same as during the installation
- (optional) Migrate pricing rules from versions 2.0.1 and 2.0.2: `bin/magento packetery:migrate-price-rules`
- (optional) Migrate default price from versions up to 2.0.5: `bin/magento packetery:migrate-default-price`
- disable maintenance mode: `bin/magento maintenance:disable`
- check configuration

#### Migration of pricing rules from versions 2.0.1 and 2.0.2

Within this task, pricing rules are migrated to the following extent: for countries from the original list of rules
are transferred the rules for the variant of delivery to the pickup point, including weight ranges and the free shipping price.

Furthermore, the maximum weight and free shipping price valid for the entire module are transferred.

Pricing rules are created as unavailable.

As of version 2.0.3, it is not necessary to perform.

#### Migration of default price from versions up to 2.0.5

Within this task, the default price is migrated only if specific countries are selected in the global settings of the module.

If all countries are selected, migration is not performed.

Price rules are created as unavailable, without set maximum weight.

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
- possibility of bulk weight adjustment in the shipment list
- export shipments to a CSV file that can be imported in [client section](https://client.packeta.com/)
- possibility to change the pickup point for an existing order in the administration
- email template variables

#### Email template variables

<code>
{{if packetery_is_pickup_point}} Pickup point: {{var packetery_point_id}} {{var packetery_point_name}} {{/if}}

{{if packetery_is_address_delivery}} Carrier name: {{var packetery_carrier_name}} {{/if}}
</code>

#### Restrictions:

- currently, the module does not support: delivery to non-EU addresses, carriers who have prohibited cash on delivery,
  evening delivery Prague, Brno, Ostrava, Bratislava

# Modul pro Magento 2

### Stažení modulu

[Aktuální verze 2.2.0](https://github.com/Zasilkovna/magento2/archive/v2.2.0.zip)

### Instalace

Instalace a registrace modulu se provádí CLI utilitou, která je součástí Magento 2.
Tato utilita je dostupná v instalačním adresáři Magenta jako "/bin/magento".

- nakopírovat adresář 'Packetery' do adresáře: `/app/code`
- povolení modulu pomocí CLI utility: `bin/magento module:enable Packetery_Checkout --clear-static-content`
- registrace modulu: `bin/magento setup:upgrade`
- re-deploy statického obsahu (není potřeba v dev módu): `bin/magento setup:static-content:deploy`
- rekompilace projektu: `bin/magento setup:di:compile`
- smazání cache: `bin/magento cache:clean`
- nastavte v administraci přepravce Zásilkovna pro výchozí kontext (scope), ikdyž je přepravce neaktivní
- nahrajte přepravce: `bin/magento packetery:import-feed-carriers`
- nastavte si v cronu volání příkazu `bin/magento cron:run` tak, aby se vám aktualizovali přepravci.

### Aktualizace modulu

- nastavte v administraci přepravce Zásilkovna pro výchozí kontext (scope), ikdyž je přepravce neaktivní
- zapnout režim údržby: `bin/magento maintenance:enable`
- smazat zdrojové soubory (smazat složku app/code/Packetery)
- další postup stejný jako při instalaci
- (nepovinné) Migrace cenových pravidel z verzí 2.0.1 a 2.0.2: `bin/magento packetery:migrate-price-rules`
- (nepovinné) Migrace výchozí ceny do verze 2.0.5: `bin/magento packetery:migrate-default-price`
- vypnout režim údržby: `bin/magento maintenance:disable`
- zkontrolovat konfiguraci

#### Migrace cenových pravidel z verzí 2.0.1 a 2.0.2

V rámci této úlohy se migrují cenová pravidla v tomto rozsahu: pro země z původního seznamu pravidel
jsou pro variantu doručení na výdejní místo přenesena pravidla včetně hmotnostních rozsahů a ceny pro dopravu zdarma.

Dále je přenesena i maximální hmotnost a cena pro dopravu zdarma platné pro celý modul.

Cenová pravidla jsou vytvořena jako nedostupná.

Od verze 2.0.3 není nutné provádět.

#### Migrace výchozí ceny do verze 2.0.5

V rámci této úlohy se migruje výchozí cena pouze v případě, že v globálním nastavení modulu jsou zvoleny specifické země.

V případě, že jsou zvoleny všechny země, migrace se neprovádí. 

Cenová pravidla jsou vytvořena jako nedostupná, bez nastavené maximální hmotnosti.

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
- možnost hromadné úpravy hmotnosti v seznamu zásilek
- export zásilek do csv souboru, který lze importovat v [klientské sekci](https://client.packeta.com/)
- možnost změny výdejního místa u existující objednávky v administraci
- proměnné pro emailové šablony

#### Proměnné pro emailové šablony

<code>
{{if packetery_is_pickup_point}} Pickup point: {{var packetery_point_id}} {{var packetery_point_name}} {{/if}}

{{if packetery_is_address_delivery}} Carrier name: {{var packetery_carrier_name}} {{/if}}
</code>

#### Omezení:

- v současné době modul nepodporuje: doručení na adresu mimo EU, dopravce kteří mají zakázanou dobírku, večerní doručení Praha, Brno, Ostrava, Bratislava
