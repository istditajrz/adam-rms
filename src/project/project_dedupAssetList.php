<?php
require_once __DIR__ . '/../common/headSecure.php';
require_once __DIR__ . '../../api/projects/data.php'; //where all the data comes from

function type_key($asset)
{
    if ($asset['asset_definableFields_ARRAY'] != null) {
        return strval($asset['assetTypes_id']);
    }

    // Checks if 'Length' is the first 6 chars
    $field_1 = $asset['assetTypes_definableFields'];
    $substring = substr($field_1, 0, 6);
    if (strtolower($substring) == 'length') {
        return strval($asset['assetTypes_id']) . "-L-" . $asset['asset_definableFields_1'];
    }
    return strval($asset['assetTypes_id']);
}

function increment_or_add($asset, &$assetList)
{
    $key = type_key($asset);
    if (array_key_exists($key, $assetList)) {
        $assetList[$key]['quantity']++;
        $assetList[$key]['assets'][] = $asset;
    } else {
        $assetList[$key] = array_intersect_key(
            $asset,
            // including:
            [
                "assetCategories_fontAwesome"       => 0,
                "assetCategories_id"                => 0,
                "assetTypes_definableFields"        => 0,
                "assetTypes_definableFields_ARRAY"  => 0,
                "assetTypes_description"            => 0,
                "assetTypes_id"                     => 0,
                "assetTypes_name"                   => 0,
                "asset_definableFields_1"           => 0,
                "asset_definableFields_2"           => 0,
                "asset_definableFields_3"           => 0,
                "asset_definableFields_4"           => 0,
                "asset_definableFields_5"           => 0,
                "asset_definableFields_6"           => 0,
                "asset_definableFields_7"           => 0,
                "asset_definableFields_8"           => 0,
                "asset_definableFields_9"           => 0,
                "asset_definableFields_10"          => 0,
                "assetsAssignmentsStatus_id"        => 0,
                "assetsAssignmentsStatus_name"      => 0,
                "assetsAssignmentsStatus_order"     => 0,
                "manufacturers_id"                  => 0,
                "manufacturers_name"                => 0,
            ]
        );
        $assetList[$key]['quantity'] = 1;
        $assetList[$key]['assets'] = [$asset];
    }
}

// { 'n': [{asset_type}] }
$sortedAssets = [];
foreach ($PAGEDATA['assetsAssignmentsStatus'] as $status) {
    $tempAssets = [];
    foreach ($PAGEDATA['FINANCIALS']['assetsAssigned'] as $assetType) {
        foreach ($assetType['assets'] as $asset) {
            if ($asset['assetsAssignmentsStatus_order'] == null && $status['assetsAssignmentsStatus_order'] == 0) { //if asset status is null, add to the first column
                increment_or_add($asset, $tempAssets);
            } elseif ($asset['assetsAssignmentsStatus_order'] == $status['assetsAssignmentsStatus_order']) {
                increment_or_add($asset, $tempAssets);
            }
        }
    }
    $sortedAssets[$AUTH->data['instance']['instances_id']][$status['assetsAssignmentsStatus_order']] = $status;
    $sortedAssets[$AUTH->data['instance']['instances_id']][$status['assetsAssignmentsStatus_order']]["assets"] = $tempAssets;
}
foreach ($PAGEDATA['FINANCIALS']['assetsAssignedSUB'] as $instance) { //Go through the sub projects
    $DBLIB->orderBy("assetsAssignmentsStatus_order", "ASC");
    $DBLIB->where("assetsAssignmentsStatus.instances_id", $instance['instance']['instances_id']);
    $DBLIB->where("assetsAssignmentsStatus.assetsAssignmentsStatus_deleted", 0);
    $sortedAssets[$instance['instance']['instances_id']] = $DBLIB->get("assetsAssignmentsStatus");
    foreach ($sortedAssets[$instance['instance']['instances_id']] as $status) {
        $tempAssets = [];
        foreach ($instance["assets"] as $assetType) {
            foreach ($assetType['assets'] as $asset) {
                if ($asset['assetsAssignmentsStatus_order'] == null && $status['assetsAssignmentsStatus_order'] == 0) { //if asset status is null, add to the first column
                    increment_or_add($asset, $tempAssets);
                } elseif ($asset['assetsAssignmentsStatus_order'] == $status['assetsAssignmentsStatus_order']) {
                    increment_or_add($asset, $tempAssets);
                }
            }
        }
        $sortedAssets[$instance['instance']['instances_id']][$status['assetsAssignmentsStatus_order']]["assets"] = $tempAssets;
    }
}

echo $TWIG->render('project/twigIncludes/dedupeCard.twig', $sortedAssets);
