<?php
/**
 * Helper function to get product display name
 * Returns formatted product name for display
 */
function getProductDisplayName($product) {
    if (isset($product['ItemName'])) {
        return $product['ItemName'];
    }
    return 'Unknown Product';
}
?>
