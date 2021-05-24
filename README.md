[Návod v češtině](https://github.com/Zasilkovna/magento2#modul-pro-magento-2)
    
# Module for Magento 2

### Download module

[Current version 2.0.5](https://github.com/Zasilkovna/magento2/archive/v2.0.5.zip)

### Installation

Installation and registration of the module is done by CLI utility, which is part of Magento 2. This utility is available in Magento installation directory as "/bin/magento".

- copy directory 'Packetery' to directory: `/app/code`
- enable module using CLI utility: `bin/magento module:enable Packetery_Checkout --clear-static-content`
- registration of module: `bin/magento setup:upgrade`
- re-deploy static content (not needed in dev mode): `bin/magento setup:static-content:deploy`
- project recompiling: `bin/magento setup:di:compile`
- clean cache: `bin/magento cache:clean`

## Upgrading

- enable maintenance mode
- remove all previous source files (remove app/code/Packetery folder)
- next steps are same as during installation
- disable maintenance mode
- check configuration

### Configuration

Login to the administration, in the left menu select **Stores**. Then select in the newly expanded menu **Settings** / **Configuration**.
Module configuration can be found in section **Packeta** - configuration.
The configuration is  divided into several parts: *Widget configuration*, *Price rules*, *Cash on delivery*. In each part fill in required informations.
and save the settings pressing the **Save config** button.

#### Widget configuration

- **API key** - you can find it in [client section](https://client.packeta.com/en/support/) » Client support

#### Price rules

##### Global settings
- **default price** - the shipping price applies if the country-specific default price is not filled
- **Maximum weight** - for orders with a larger weight, the Packeta shipping method will not be offered in the cart
- **free shipping** - if the order price is higher, free shipping

##### Rules - other countries

These rules are not currently applied. They will be removed in the next version of the module.

##### Rules CZ (SK, PL, HU, RO)

Enter prices and shipping pricing rules for each supported country here.

- **default price** - the price will be applied if you do not fill in the pricing rules, or the order weight exceeds the set weighting rules for a particular country
- **free shipping** - if the order price is higher, free shipping
- **price rules** - here you can add more pricing rules for different weight ranges.
    - to create a new rule click on the button * Add Rule *
    - click the * Delete * button to delete the rule
    - fill in the fields * Weight from *, * Weight to * and * Price * for each rule

#### Cash on delivery

Under * Cash on delivery * - * Payment methods *, select the payment methods that will be considered as cash on delivery (for Packeta).
Multiple payment methods can be selected by holding the "Ctrl" button and clicking on the required payment methods

### List of orders

- To enter the order list, select in the left menu **Packeta**.
- Export orders to the CSV file:
    - Select the orders you want to export to CSV file.
    - Above the list of orders you will find the ** Actions ** drop-down menu where you select ** CSV export ** and click ** Search **

### Informations about the module

#### Supported languages:

- czech
- english

#### Supported versions:

- Magento 2.2 and newer
- If you have a problem using the Magento 2 module (eg 2.0), please contact us at [support@packeta.com](mailto:support@packeta.com)

#### Supported features:

- integration of widget v6 for pickup points selections in the eshop cart
- external carrier pickup point support
- address delivery support (in cz, sk, hu, pl and ro via "Home delivery HD")
- set different prices for different target countries
- setting prices according to weighting rules
- free shipping from the specified price or weight of the order
- export orders to a csv file that can be imported in [client section](https://client.packeta.com/)

# Modul pro Magento 2

### Stažení modulu

[Aktuální verze 2.0.5](https://github.com/Zasilkovna/magento2/archive/v2.0.5.zip)

### Instalace

Instalace a registrace modulu se provádí CLI utilitou, která je součástí Magento 2. Tato utitita je dostupná v instalačním adresáři Magenta jako "/bin/magento".

- nakopírovat adresář 'Packetery' do adresáře: `/app/code`
- povolení modulu pomocí CLI utility: `bin/magento module:enable Packetery_Checkout --clear-static-content`
- registrace modulu: `bin/magento setup:upgrade`
- re-deploy statického obsahu (není potřeba v dev módu): `bin/magento setup:static-content:deploy`
- rekompilace projektu: `bin/magento setup:di:compile`
- smazání cache: `bin/magento cache:clean`

### Aktualizace modulu

- zapnout režim údržby
- smazat zdrojové soubory (smazat složku app/code/Packetery)
- další postup stejný jako při instalaci
- vypnout režim údržby
- zkontrolovat konfiguraci

### Konfigurace

Přihlašte se do administrace, v levém menu vyberte **Stores**.  Poté v nově rozbaleném menu vyberte položku **Settings** / **Configuration**.
Konfiguraci modulu najdete v sekci **Zásilkovna** konfigurační stránky.
Konfigurace je rozdělena do několika částí:  *Nastavení widgetu*, *Cenová pravidla*, *Dobírka*.   V každé části je vyplňte požadované údaje 
a nastavení uložte kliknutím na tlačítko **Save Config**

#### Nastavení widgetu 

- **API klíč** - naleznete jej v [klientské sekci](https://client.packeta.com/cs/support/) » Klientská podpora

#### Cenová pravidla

##### Globální nastavení
- **Výchozí cena** - cena za přepravu se použije v případě, že není vyplněna výchozí cena u konkrétní země
- **Maximální váha** - u objednávek s větší hmotnostní nebude v košíku přepravní metoda Zásilkovna nabízena
- **Doprava zdarma** - pokud bude cena objednávky vyšší bude doprava zdarma

##### Pravidla - ostatní země

Tato pravidla se v současné době nepoužívají.  V příští verzi modulu budou odstraněna.

##### Pravidla CZ (SK, PL, HU, RO)

Zde zadejte ceny a pravidla pro výpočet ceny přepravy pro každou podporovanou zemi zvlášť.

- **Výchozí cena** - cena se použije pokud nevyplníte cenová pravidla, nebo hmotnost objednávky přesáhne nastavená váhová pravidla pro konkrétní zemi
- **Doprava zdarma** - pokud bude cena objednávky vyšší bude doprava zdarma
- **Cenová pravidla** - zde můžete přidat více cenových pravidel, pro různá váhová rozmezí.  
    - pro vytvoření nového pravidla klikněte na tlačítko *Přidat pravidlo*
    - pro smazání pravidla klikněte na ikonu popelnice
    - u každého pravidla vyplňte pole *Hmotnost od*, *Hmotnost do* a *Cena*

#### Dobírka

V části *Dobírka* - *Platební metody* vyberte platební metody, které budou považovány za platební metody na dobírku (pro Zásikovnu).
Vybrat více platebních metod je možné přidržením tlačítka "Ctrl" a kliknutím na jednotlivé požadované platební metody

### Seznam objednávek

- Pro vstup do seznamu objednávek Zásilkovny zvolte v levém menu položku **Zásilkovna**.
- Export zásilek do CSV souboru:
    - Označte objednávky které chcete exportovat do CSV souboru.
    - Nad seznamem objednávek naleznete rozbalovací menu **Actions** kde vyberete **CSV export** a kliknete na tlačítko **Search**

### Informace o modulu

#### Podporované jazyky:

- čeština
- angličtina

#### Podporované verze:

- Magento 2.2 a vyšší
- Při problému s použitím modulu u nižší verze Magento 2 (např. 2.0) nás kontaktujte na adrese [technicka.podpora@zasilkovna.cz](mailto:technicka.podpora@zasilkovna.cz)

#### Poskytované funkce:

- integrace widgetu v6 v košíku eshopu
- podpora výdejních míst externích dopravců
- podpora doručení zásilek na adresu (v cz, sk, hu, pl a ro přes dopravce “Doručení na adresu HD”)
- nastavení různé ceny pro různé cílové země
- nastavení cen podle váhových pravidel
- doprava zdarma od zadané ceny nebo hmotnosti objednávky
- export zásilek do csv souboru, který lze importovat v [klientské sekci](https://client.packeta.com/)
