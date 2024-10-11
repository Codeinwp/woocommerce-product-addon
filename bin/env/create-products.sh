# Create products and collect their IDs
product1_id=$(wp wc product create --name="Product 1" --type="simple" --regular_price="9.99" --user=admin --porcelain)
product2_id=$(wp wc product create --name="Product 2" --type="simple" --regular_price="19.99" --user=admin --porcelain)
product3_id=$(wp wc product create --name="Product 3" --type="simple" --regular_price="29.99" --user=admin --porcelain)

# Get the first category.
category_id=$(wp wc product_cat list --user=admin --format=ids | awk '{print $1}')
echo "Category ID: $category_id"

# Add products to the first category
wp wc product update $product1_id --user=admin --categories='[{"id": '$category_id'}]'
wp wc product update $product2_id --user=admin --categories='[{"id": '$category_id'}]'
wp wc product update $product3_id --user=admin --categories='[{"id": '$category_id'}]'