<?php

declare(strict_types=1);

use WHMCS\Database\Capsule as DB;

add_hook('AdminAreaPage', 1, function (array $vars): void {
    if (($_POST['ajaxpage'] ?? '') === 'createconfig') {
        require_once 'cloudstack.php';

        $productId = (int) ($_POST['productid'] ?? 0);
        if ($productId <= 0) {
            echo "Invalid product ID";
            exit();
        }

        $data = DB::table('tblproducts')
            ->where('id', '=', $productId)
            ->where('servertype', '=', 'cloudstack')
            ->first();

        if (!$data) {
            echo "Product not found or invalid server type";
            exit();
        }

        $params = [
            'configoption3' => $data->configoption3,
            'configoption1' => $data->configoption1,
            'configoption2' => $data->configoption2,
            'data' => $data->configoption2
        ];

        $cloudstack = request($params);

        $zones = $cloudstack->listZones(); // Zones list
        $zone = [];
        foreach ($zones->listzonesresponse->zone ?? [] as $zonename) {
            $zone[$zonename->id] = $zonename->name;
        }

        $serviceoffers = $cloudstack->listServiceOfferings(); // Disk offers
        $soffer = [];
        foreach ($serviceoffers->listserviceofferingsresponse->serviceoffering ?? [] as $serviceoffer) {
            $soffer[$serviceoffer->id] = $serviceoffer->name;
        }

        $disk = [];
        $diskoffers = $cloudstack->listDiskOfferings(); // Disk offers
        foreach ($diskoffers->listdiskofferingsresponse->diskoffering ?? [] as $diskoffer) {
            $disk[$diskoffer->id] = $diskoffer->name;
        }

        $temp = [];
        $templates = $cloudstack->listTemplates('all');
        foreach ($templates->listtemplatesresponse->template ?? [] as $template) {
            $temp[$template->id] = $template->name;
        }

        cloudstack_generateconfigoption('Zones', $productId, $zone);
        cloudstack_generateconfigoption('ServiceOffer', $productId, $soffer);
        cloudstack_generateconfigoption('DiskOffer', $productId, $disk);
        cloudstack_generateconfigoption('Template', $productId, $temp);

        echo "success";
        exit();
    }
});
