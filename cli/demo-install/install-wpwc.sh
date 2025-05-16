#!/bin/bash

DB_HOST="dev_db"
DB_ADMIN_USER="root"
DB_ADMIN_PASS="your-root-password"

NEW_DB_USER="newwp_dbuser"
NEW_DB_PASS="tesTing87"
NEW_DB_NAME="newwp"

NEW_WP_ROOT="/app/newwp"
NEW_WP_USER="AdminUser"
NEW_WP_PASS="your-Admin-password."
NEW_WP_MAIL="shopmodules.demo@zasilkovna.cz"
NEW_WP_TITLE="NewWP"
NEW_WP_URL="newwp.dev.vm"
NEW_WP_VERSION="latest"
# more exotic locales do not work with latest version
NEW_WP_LOCALE="en_US"

category_name_with_disallowed_shipping="Tshirts"
category_taxonomy="product_cat"

# RUN

if ! command -v php 2>&1 >/dev/null;
then
	echo "🛑 php could not be found, run 'apt-get install php'"
	exit 1
fi

if ! command -v mysql 2>&1 >/dev/null
then
	echo "🛑 mysql could not be found, run 'apt-get install default-mysql-client'"
	exit 1
fi

if ! mysql -h$DB_HOST -u$DB_ADMIN_USER -p$DB_ADMIN_PASS -e ";"
then
	echo "🛑 Can't connect connect to database, invalid password probably."
	exit 1
fi


echo '➤ Deleting site root...'
rm -rf $NEW_WP_ROOT/*

echo '➤ Dropping database...'
mysql -h$DB_HOST -u$DB_ADMIN_USER -p$DB_ADMIN_PASS -e "DROP DATABASE ${NEW_DB_NAME};"
mysql -h$DB_HOST -u$DB_ADMIN_USER -p$DB_ADMIN_PASS -e "SHOW DATABASES LIKE '${NEW_DB_NAME}';"

echo '➤ Dropping database user...'
mysql -h$DB_HOST -u$DB_ADMIN_USER -p$DB_ADMIN_PASS -e "DROP USER '${NEW_DB_USER}';"
mysql -h$DB_HOST -u$DB_ADMIN_USER -p$DB_ADMIN_PASS -e "SELECT Host, User FROM mysql.user WHERE User = '${NEW_DB_USER}';"

echo '➤ Creating database and user...'
mysql -h$DB_HOST -u$DB_ADMIN_USER -p$DB_ADMIN_PASS -e "CREATE DATABASE ${NEW_DB_NAME};"
mysql -h$DB_HOST -u$DB_ADMIN_USER -p$DB_ADMIN_PASS -e "CREATE USER '${NEW_DB_USER}'@'%' IDENTIFIED BY '${NEW_DB_PASS}';"
mysql -h$DB_HOST -u$DB_ADMIN_USER -p$DB_ADMIN_PASS -e "GRANT ALL PRIVILEGES ON ${NEW_DB_NAME}.* TO '${NEW_DB_USER}'@'%'; FLUSH PRIVILEGES;"

echo '➤ Creating site root...'
mkdir $NEW_WP_ROOT

SCRIPT_CWD=$(pwd)
cd $NEW_WP_ROOT

echo '➤ Downloading WordPress...'
if ! wp core download --locale="${NEW_WP_LOCALE}" --version="${NEW_WP_VERSION}" --allow-root;
then
	echo "🛑 Failed to download WordPress (locale: ${NEW_WP_LOCALE}, version: ${NEW_WP_VERSION})"
	exit 1
fi
echo '➤ Creating config...'
wp config create --dbhost=$DB_HOST --dbname=$NEW_DB_NAME --dbuser=$NEW_DB_USER --dbpass=$NEW_DB_PASS --allow-root
echo '➤ Installing core...'
wp core install --url=$NEW_WP_URL --title="${NEW_WP_TITLE}" --admin_user=$NEW_WP_USER --admin_password=$NEW_WP_PASS --admin_email=$NEW_WP_MAIL --allow-root

echo '➤ Installing WooCommerce...'
wp plugin install woocommerce --allow-root
echo '➤ Activating WooCommerce...'
wp plugin activate woocommerce --allow-root

echo '➤ Setting up WooCommerce...'
wp option update fresh_site 0 --allow-root
wp option update woocommerce_default_country CZ --allow-root
wp option update woocommerce_price_thousand_sep ' ' --allow-root
wp option update woocommerce_price_decimal_sep ',' --allow-root
wp option update woocommerce_coming_soon no --allow-root
wp option update woocommerce_store_pages_only no --allow-root
wp option update woocommerce_calc_taxes yes --allow-root
wp option update woocommerce_currency CZK --allow-root
wp option update woocommerce_currency_pos right_space --allow-root
wp option update woocommerce_dimension_unit cm --allow-root
wp option update woocommerce_weight_unit kg --allow-root
wp wc payment_gateway update cod --enabled=1 --user="$NEW_WP_USER" --allow-root
wp wc payment_gateway update bacs --enabled=1 --user="$NEW_WP_USER" --allow-root
wp wc payment_gateway update cheque --enabled=1 --user="$NEW_WP_USER" --allow-root

wp user meta update 1 last_update 1746057600 --allow-root
wp user meta update 1 woocommerce_admin_task_list_tracked_started_tasks --format=json '{"launch-your-store":1}' --allow-root
wp user meta update 1 dismissed_no_secure_connection_notice 1 --allow-root
wp option update woocommerce_coming_soon no --allow-root
wp option update woocommerce_store_pages_only yes --allow-root
wp option update woocommerce_admin_created_default_shipping_zones yes --allow-root
wp option update woocommerce_admin_reviewed_default_shipping_zones yes --allow-root
wp option update woocommerce_private_link no --allow-root
wp option update woocommerce_store_pages_only yes --allow-root
wp option update finished_updating_comment_type 1 --allow-root
wp option update woocommerce_task_list_tracked_completed_tasks --format=json '["products","payments","tax","shipping","review-shipping","launch-your-store"]' --allow-root
wp option update woocommerce_onboarding_profile_progress --format=json '{"core_profiler_completed_steps":{"intro-opt-in":{"completed_at":"2025-05-01T00:00:00Z"},"skip-guided-setup":{"completed_at":"2025-05-01T00:00:00Z"}}}' --allow-root
wp option update woocommerce_onboarding_profile --format=json '{"skipped":true}' --allow-root


echo '➤ Preparing category sample data...'
php $SCRIPT_CWD/parse-product-csv.php category $NEW_WP_USER 2>$SCRIPT_CWD/sample-categories-errors.log >$SCRIPT_CWD/sample-categories.sh
if [ ! -s $SCRIPT_CWD/sample-categories-errors.log ]; then
	rm $SCRIPT_CWD/sample-categories-errors.log
	if [ ! -s $SCRIPT_CWD/sample-categories.sh ]; then
		echo '🛑 Preparing sample data unexpectedly failed.'
		exit 1
	fi
	echo '➤ Importing sample data...'
	bash $SCRIPT_CWD/sample-categories.sh
else
	echo '🛑 Preparing sample data failed, skipping execution:'
	cat $SCRIPT_CWD/sample-categories-errors.log
fi

echo '➤ Exporting list of categories...'
wp wc product_cat list --user="$NEW_WP_USER" --allow-root >$SCRIPT_CWD/categories.tsv


echo '➤ Preparing product sample data...'
php $SCRIPT_CWD/parse-product-csv.php product $NEW_WP_USER 2>$SCRIPT_CWD/sample-products-errors.log >$SCRIPT_CWD/sample-products.sh
if [ ! -s $SCRIPT_CWD/sample-products-errors.log ]; then
	rm $SCRIPT_CWD/sample-products-errors.log
	if [ ! -s $SCRIPT_CWD/sample-products.sh ]; then
		echo '🛑 Preparing sample data unexpectedly failed.'
		exit 1
	fi
	echo '➤ Importing sample data...'
	bash $SCRIPT_CWD/sample-products.sh
else
	echo '🛑 Preparing sample data failed, skipping execution:'
	cat $SCRIPT_CWD/sample-products-errors.log
fi


wp term list $category_taxonomy --allow-root >$SCRIPT_CWD/terms.tsv
term_id=$(awk -F'\t' -v name="$category_name_with_disallowed_shipping" '$3 == name { print $1 }' $SCRIPT_CWD/terms.tsv)
if [ -n "$term_id" ]; then
	echo "➤ Category ID for '$category_name_with_disallowed_shipping' is: $term_id"
	wp term meta update $term_id packetery_disallowed_shipping_rates_by_cat --format=json '{"packetery_carrier_106":true}' --allow-root
else
	echo "🛑 Category '$category_name_with_disallowed_shipping' not found."
fi


# Accepts a plugin slug, the path to a local zip file, or a URL to a remote zip file.
echo '➤ Installing Packeta plugin...'
wp plugin install packeta --allow-root
echo '➤ Activating Packeta plugin...'
wp plugin activate packeta --allow-root
wp option update _transient_packeta_split_message_dismissed yes --allow-root
# is only capable of adding classic carrier methods
#wp option update packetery_advanced --format=json '{"new_carrier_settings_enabled":true}' --allow-root


#### Zones start.
declare -A ZONES=(
	["Czech Republic"]="CZ"
	["Slovakia"]="SK"
	["Hungary"]="HU"
	["Poland"]="PL"
	["Germany"]="DE"
	["Ukraine"]="UA"
)

declare -A ZONE_METHODS=(
	["Czech Republic"]="packetery_shipping_method flat_rate" # packeta_method_106
	["Slovakia"]="packetery_shipping_method flat_rate"
	["Hungary"]="packetery_shipping_method"
	["Poland"]="packetery_shipping_method flat_rate" # packeta_method_3060
	["Germany"]=""
	["Ukraine"]="packetery_shipping_method flat_rate"
)

for ZONE_NAME in "${!ZONES[@]}"; do
	COUNTRY_CODE="${ZONES[$ZONE_NAME]}"
	OUTPUT=$(wp wc shipping_zone create --name="$ZONE_NAME" --user="$NEW_WP_USER" --allow-root 2>&1)

	if [[ $OUTPUT =~ Success:\ Created\ shipping_zone\ ([0-9]+)\. ]]; then
		ZONE_ID="${BASH_REMATCH[1]}"
		echo "➤ Created zone '$ZONE_NAME' with ID $ZONE_ID"

		# Assign location using eval
		wp eval "
			\$zone = new WC_Shipping_Zone($ZONE_ID);
			\$zone->set_locations([['code' => '$COUNTRY_CODE', 'type' => 'country']]);
			\$zone->save();
		" --allow-root

		# Add shipping methods
		IFS=' ' read -r -a METHODS <<< "${ZONE_METHODS[$ZONE_NAME]}"
		for METHOD in "${METHODS[@]}"; do
			if [[ -n "$METHOD" ]]; then
				echo "➤ Adding shipping method '$METHOD' to zone '$ZONE_NAME'"
				wp wc shipping_zone_method create "$ZONE_ID" --method_id="$METHOD" --user="$NEW_WP_USER" --allow-root
			fi
		done
	else
		echo "🛑 Failed to create zone '$ZONE_NAME':"
		echo "$OUTPUT"
	fi
done
#### Zones end.


#### Tax rates start.
# Countries and their display names (used in tax name)
declare -A COUNTRIES=(
	["CZ"]="CZ Tax"
	["DE"]="DE Tax"
	["HU"]="HU Tax"
	["PL"]="PL Tax"
	["RO"]="RO Tax"
	["SK"]="SK Tax"
)

ORDER=0
for COUNTRY in "${!COUNTRIES[@]}"; do
	NAME="${COUNTRIES[$COUNTRY]}"

	wp wc tax create --country="$COUNTRY" --rate="21.0000" --name="$NAME" --priority=1 --shipping=1 --order="$ORDER" --class="standard" --user="$NEW_WP_USER" --allow-root
	wp wc tax create --country="$COUNTRY" --rate="0.0000" --name="$NAME" --priority=1 --shipping=1 --order="$ORDER" --class="zero-rate" --user="$NEW_WP_USER" --allow-root
	wp wc tax create --country="$COUNTRY" --rate="12.0000" --name="$NAME" --priority=1 --shipping=1 --order="$ORDER" --class="reduced-rate" --user="$NEW_WP_USER" --allow-root

	ORDER=$((ORDER + 1))
done
#### Tax rates end.


echo '➤ Final chown...'
chown -R www-data:www-data $NEW_WP_ROOT

echo '✓ Done.'
