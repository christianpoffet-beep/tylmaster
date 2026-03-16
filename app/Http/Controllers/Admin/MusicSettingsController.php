<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class MusicSettingsController extends Controller
{
    public function creditRoles()
    {
        $roles = Setting::creditRoles();
        return view('admin.settings.credit-roles', compact('roles'));
    }

    public function updateCreditRoles(Request $request)
    {
        $groups = $request->input('groups', []);
        $roles = [];

        foreach ($groups as $group) {
            $groupName = trim($group['name'] ?? '');
            if (!$groupName) continue;

            $items = [];
            foreach ($group['items'] ?? [] as $item) {
                $key = trim($item['key'] ?? '');
                $label = trim($item['label'] ?? '');
                if ($key && $label) {
                    $items[$key] = $label;
                }
            }
            if (!empty($items)) {
                $roles[$groupName] = $items;
            }
        }

        Setting::set('credit_roles', $roles);
        return redirect()->route('admin.settings.credit-roles')->with('success', 'Track-Credits gespeichert.');
    }

    public function instruments()
    {
        $instruments = Setting::instruments();
        return view('admin.settings.instruments', compact('instruments'));
    }

    public function updateInstruments(Request $request)
    {
        $groups = $request->input('groups', []);
        $instruments = [];

        foreach ($groups as $group) {
            $groupName = trim($group['name'] ?? '');
            if (!$groupName) continue;

            $items = [];
            foreach ($group['items'] ?? [] as $item) {
                $name = trim($item ?? '');
                if ($name) {
                    $items[] = $name;
                }
            }
            if (!empty($items)) {
                $instruments[$groupName] = $items;
            }
        }

        Setting::set('instruments', $instruments);
        return redirect()->route('admin.settings.instruments')->with('success', 'Instrumente gespeichert.');
    }

    public function productTypes()
    {
        $productTypes = Setting::productTypes();
        return view('admin.settings.product-types', compact('productTypes'));
    }

    public function updateProductTypes(Request $request)
    {
        $groups = $request->input('groups', []);
        $productTypes = [];

        foreach ($groups as $group) {
            $groupName = trim($group['name'] ?? '');
            if (!$groupName) continue;

            $items = [];
            foreach ($group['items'] ?? [] as $item) {
                $name = trim($item ?? '');
                if ($name) {
                    $items[] = $name;
                }
            }
            if (!empty($items)) {
                $productTypes[$groupName] = $items;
            }
        }

        Setting::set('product_types', $productTypes);
        return redirect()->route('admin.settings.product-types')->with('success', 'Produkttypen gespeichert.');
    }
}
