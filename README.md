[Návod v češtině](https://github.com/Zasilkovna/magento2#modul-pro-magento-2)
    
# Module for Magento 2

### Download module

[Current version 2.1.0](https://github.com/Zasilkovna/magento2/archive/v2.1.0.zip)

### Installation

Installation and registration of the module is done by CLI utility, which is part of Magento 2. This utility is available in Magento installation directory as "/bin/magento".

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
- **Maximum weight** - for orders with a larger weight, the Packeta shipping method will not be offered in the cart
- **free shipping** - if the order price is higher, free shipping

##### Rules

Enter prices and shipping pricing rules for each supported country here. Only kilograms are supported.

- **free shipping** - if the order price is higher, free shipping
- **price rules** - here you can add more pricing rules for different weight ranges.
    - to create a new rule click on the button * Add Rule *
    - click the * Delete * button to delete the rule
    - fill in the fields * Weight to * and * Price * for each rule

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

- Magento 2.3, 2.4
- If you have a problem using the module, please contact us by email: [support@packeta.com](mailto:support@packeta.com)

#### Supported features:

- integration of widget v6 in the cart
- support for external carriers' pickup points
- delivery to an address via external carriers
- setting different prices for each carrier
- free shipping from the specified price
- export shipments to a csv file that can be imported in [client section](https://client.packeta.com/)
- possibility to change the pickup point for an existing order in the administration

#### Restrictions:

- currently the module does not support: delivery to non-EU addresses, carriers who have prohibited cash on delivery, evening delivery Prague, Brno, Ostrava, Bratislava

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
- **Maximální váha** - u objednávek s větší hmotnostní nebude v košíku přepravní metoda Zásilkovna nabízena
- **Doprava zdarma** - pokud bude cena objednávky vyšší bude doprava zdarma

##### Pravidla

Zde zadejte ceny a pravidla pro výpočet ceny přepravy pro každou podporovanou zemi zvlášť. Podporované jsou pouze váhy v kilogramech.

- **Doprava zdarma** - pokud bude cena objednávky vyšší bude doprava zdarma
- **Cenová pravidla** - zde můžete přidat více cenových pravidel, pro různá váhová rozmezí.  
    - pro vytvoření nového pravidla klikněte na tlačítko *Přidat pravidlo*
    - pro smazání pravidla klikněte na ikonu popelnice
    - u každého pravidla vyplňte pole *Hmotnost do* a *Cena*

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
