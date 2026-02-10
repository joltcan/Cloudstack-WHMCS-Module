<?php

declare(strict_types=1);

use WHMCS\Database\Capsule as DB;

function cloudstack_ConfigOptions(): array
{
    $id = (int)($_REQUEST["id"] ?? 0);
    if ($id <= 0) {
        return [];
    }

    $customFields = [
        'domainid',
        'cloudstackvmid',
        'cloudstackaccountid',
        'networkid',
        'ipstart|IP Start',
        'ipend|IP End',
        'netmask|NetMask',
        'networktype|Choose Network Type',
        'domain|Domain Name',
        'gateway|Gateway'
    ];

    foreach ($customFields as $fieldName) {
        $exists = DB::table("tblcustomfields")
            ->where('relid', $id)
            ->where('fieldname', 'LIKE', "%$fieldName%")
            ->where('type', 'product')
            ->exists();

        if (!$exists) {
            DB::table("tblcustomfields")->insert([
                "type" => "product",
                "relid" => $id,
                "fieldname" => $fieldName,
                "fieldtype" => str_contains($fieldName, 'networktype') ? "dropdown" : "text",
                "adminonly" => str_contains($fieldName, 'cloudstack') ? "on" : "off",
                "required" => str_contains($fieldName, '|') ? "on" : "off",
                "showorder" => str_contains($fieldName, '|') ? "on" : "off",
                "fieldoptions" => str_contains($fieldName, 'networktype') ? "Shared,Guest,Isolated" : ""
            ]);
        }
    }

    return [
        "API Key" => [
            "FriendlyName" => "API Key",
            "Type" => "text",
            "Size" => "25",
        ],
        "Secret Key" => [
            "FriendlyName" => "Secret Key",
            "Type" => "text",
            "Size" => "25",
        ],
        "End Point" => [
            "FriendlyName" => "End Point",
            "Type" => "text",
            "Size" => "25",
            "Description" => "API url",
        ],
        "License" => [
            "FriendlyName" => "License",
            "Type" => "text",
            "Size" => "25",
        ],
        "Memory" => [
            "FriendlyName" => "Memory",
            "Type" => "text",
            "Size" => "25",
            "Description" => "In GB",
        ],
        "create_opt" => [
            "FriendlyName" => "",
            "Description" => "<a href='#' id='createoption'>Create Config Option</a><script>
            $(document).ready(function(){
                $('#createoption').click(function() {
                    $.ajax({
                       url: 'index.php',
                       method: 'POST',
                       data: 'ajaxpage=createconfig&productid=$id',
                       success: function(data){
                            console.log(data);
                            window.location.href='configproducts.php?action=edit&id=$id&tab=3#tab=3';
                       }
                    });
                });
            });
        </script>"
        ],
    ];
}

function cloudstack_generateconfigoption(string $optionName, int $productId, array $options): void
{
    // 1. Find or create a config group for the product.
    $product = DB::table('tblproducts')->find($productId);
    if (!$product) {
        // Product not found, can't proceed.
        return;
    }
    $groupName = "CloudStack Options for {$product->name}";
    $configGroup = DB::table('tblproductconfiggroups')->where('name', $groupName)->first();

    if (!$configGroup) {
        $groupId = DB::table('tblproductconfiggroups')->insertGetId([
            'name' => $groupName,
            'description' => "Auto-generated for product '{$product->name}' by the CloudStack module."
        ]);
    } else {
        $groupId = $configGroup->id;
    }

    // 2. Link the product to the group if not already linked.
    $linkExists = DB::table('tblproductconfiglinks')->where('gid', $groupId)->where('pid', $productId)->exists();
    if (!$linkExists) {
        DB::table('tblproductconfiglinks')->insert(['gid' => $groupId, 'pid' => $productId]);
    }

    // 3. Find or create the configurable option within that group.
    $configOption = DB::table('tblproductconfigoptions')
        ->where('gid', $groupId)
        ->where('optionname', $optionName)
        ->first();

    if (!$configOption) {
        $configId = DB::table('tblproductconfigoptions')->insertGetId([
            'gid' => $groupId,
            'optionname' => $optionName,
            'optiontype' => 'dropdown',
            'qtyminimum' => 0,
            'qtymaximum' => 0,
            'order' => 0,
            'hidden' => 0,
        ]);
    } else {
        $configId = $configOption->id;
        // Clear existing sub-options to refresh the list from CloudStack.
        DB::table('tblproductconfigoptionssub')->where('configid', $configId)->delete();
    }

    // 4. Populate the sub-options from the `$options` array.
    $subOptions = [];
    $sortOrder = 0;
    foreach ($options as $id => $name) {
        $subOptions[] = [
            'configid' => $configId,
            'optionname' => "$id|$name",
            'sortorder' => ++$sortOrder,
            'hidden' => 0,
        ];
    }

    if (!empty($subOptions)) {
        DB::table('tblproductconfigoptionssub')->insert($subOptions);
    }
}

function cloudstack_TerminateAccount(array $params): string
{
    $cloudstack = request($params);
    $id = $params['customfields']['cloudstackaccountid'] ?? '';
    $domain = $cloudstack->deleteAccount($id);

    return isset($domain->responsedeleteaccount->jobid) ? "success" : ($domain->responsedeleteaccount->errortext ?? "Unknown error");
}

function cloudstack_SuspendAccount(array $params): string
{
    $cloudstack = request($params);
    $id = $params['customfields']['cloudstackaccountid'] ?? '';
    $domain = $cloudstack->disableAccount(true, $params['username'] ?? '', '', $id);

    return isset($domain->disableaccountresponse->jobid) ? "success" : ($domain->responsedisableaccount->errortext ?? "Unknown error");
}

function cloudstack_UnsuspendAccount(array $params): string
{
    $cloudstack = request($params);
    $id = $params['customfields']['cloudstackaccountid'] ?? '';
    $domain = $cloudstack->enableAccount($params['username'] ?? '', '', $id);

    return !empty($domain->enableaccountresponse->account->id) ? "success" : ($domain->responseenableaccount->errortext ?? "Unknown error");
}

function cloudstack_ChangePassword(array $params): string
{
    $cloudstack = request($params);
    $id = $params['customfields']['cloudstackvmid'] ?? '';
    $reset = $cloudstack->resetPasswordForVirtualMachine($id);

    return isset($reset->passwordforvirtualmachineresponse->jobid) ? "success" : ($reset->passwordforvirtualmachineresponse->errortext ?? "Unknown error");
}

function request(array $params): CloudStackClient
{
    require_once __DIR__ . '/class/CloudStackClient.php';
    return new CloudStackClient(

        $params['configoption1'] ?? '',
        $params['configoption2'] ?? '',
      $params['configoption3'] ?? ''
    );
}

function cloudstack_reboot(array $params): string
{
    $cloudstack = request($params);
    $id = $params['customfields']['cloudstackvmid'] ?? '';
    $reboot = $cloudstack->rebootVirtualMachine($id);

    return isset($reboot->rebootvirtualmachineresponse->jobid) ? "success" : ($reboot->rebootvirtualmachineresponse->errortext ?? "Unknown error");
}

function cloudstack_shutdown(array $params): string
{
    $cloudstack = request($params);
    $id = $params['customfields']['cloudstackvmid'] ?? '';
    $stop = $cloudstack->stopVirtualMachine($id);

    return isset($stop->stopvirtualmachineresponse->jobid) ? "success" : ($stop->stopvirtualmachineresponse->errortext ?? "Unknown error");
}

function cloudstack_start(array $params): string
{
    $cloudstack = request($params);
    $id = $params['customfields']['cloudstackvmid'] ?? '';
    $start = $cloudstack->startVirtualMachine($id);

    return isset($start->startvirtualmachineresponse->jobid) ? "success" : ($start->startvirtualmachineresponse->errortext ?? "Unknown error");
}

function cloudstack_ClientAreaCustomButtonArray(): array
{
    return [
        "Reboot Server" => "reboot",
        "Shutdown Server" => "shutdown",
        "Start Server" => "start",
    ];
}

function cloudstack_AdminCustomButtonArray(): array
{
    return [
        "Reboot Server" => "reboot",
        "Shutdown Server" => "shutdown",
        "Start Server" => "start",
    ];
}
