# Create new categories
# NOTE: If the categories with the same slug are already present, it will raise an error.
echo "Created Category 1 with ID: $category1_id"
category1_id=$(wp wc product_cat create --name="Category 1" --slug="test_cat_1" --user=admin --porcelain)

echo "Created Category 2 with ID: $category2_id"
category2_id=$(wp wc product_cat create --name="Category 2" --slug="test_cat_2" --user=admin --porcelain)

echo "Created Category 3 with ID: $category3_id"
category3_id=$(wp wc product_cat create --name="Category 3" --slug="test_cat_3" --user=admin --porcelain)

# Create products and collect their IDs
product1_id=$(wp wc product create --name="Product 1" --type="simple" --regular_price="9.99" --user=admin --porcelain)
echo "Created Product 1 with ID: $product1_id"

product2_id=$(wp wc product create --name="Product 2" --type="simple" --regular_price="19.99" --user=admin --porcelain)
echo "Created Product 2 with ID: $product2_id"

product3_id=$(wp wc product create --name="Product 3" --type="simple" --regular_price="29.99" --user=admin --porcelain)
echo "Created Product 3 with ID: $product3_id"

# Add products to the new categories
wp wc product update $product1_id --user=admin --categories='[{"id": '$category1_id'}]'
wp wc product update $product2_id --user=admin --categories='[{"id": '$category2_id'}]'
wp wc product update $product3_id --user=admin --categories='[{"id": '$category3_id'}]'