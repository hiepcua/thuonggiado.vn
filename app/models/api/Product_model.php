<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
*
*/
class Product_model extends CI_Model
{
    public function addProduct($data, $items, $warehouse_qty, $product_attributes, $photos)
    {
        if ($this->db->insert('products', $data)) {
            $product_id = $this->db->insert_id();

            if ($items) {
                foreach ($items as $item) {
                    $item['product_id'] = $product_id;
                    $this->db->insert('combo_items', $item);
                }
            }

            $warehouses = $this->site->getAllWarehouses();
            if ($data['type'] != 'standard') {
                foreach ($warehouses as $warehouse) {
                    $this->db->insert('warehouses_products', ['product_id' => $product_id, 'warehouse_id' => $warehouse->id, 'quantity' => 0]);
                }
            }

            $tax_rate = $this->site->getTaxRateByID($data['tax_rate']);

            if ($warehouse_qty && !empty($warehouse_qty)) {
                foreach ($warehouse_qty as $wh_qty) {
                    if (isset($wh_qty['quantity']) && !empty($wh_qty['quantity'])) {
                        $this->db->insert('warehouses_products', ['product_id' => $product_id, 'warehouse_id' => $wh_qty['warehouse_id'], 'quantity' => $wh_qty['quantity'], 'rack' => $wh_qty['rack'], 'avg_cost' => $data['cost']]);

                        if (!$product_attributes) {
                            $tax_rate_id = $tax_rate ? $tax_rate->id : null;
                            $tax         = $tax_rate ? (($tax_rate->type == 1) ? $tax_rate->rate . '%' : $tax_rate->rate) : null;
                            $unit_cost   = $data['cost'];
                            if ($tax_rate) {
                                if ($tax_rate->type == 1 && $tax_rate->rate != 0) {
                                    if ($data['tax_method'] == '0') {
                                        $pr_tax_val    = ($data['cost'] * $tax_rate->rate) / (100 + $tax_rate->rate);
                                        $net_item_cost = $data['cost'] - $pr_tax_val;
                                        $item_tax      = $pr_tax_val * $wh_qty['quantity'];
                                    } else {
                                        $net_item_cost = $data['cost'];
                                        $pr_tax_val    = ($data['cost'] * $tax_rate->rate) / 100;
                                        $unit_cost     = $data['cost'] + $pr_tax_val;
                                        $item_tax      = $pr_tax_val * $wh_qty['quantity'];
                                    }
                                } else {
                                    $net_item_cost = $data['cost'];
                                    $item_tax      = $tax_rate->rate;
                                }
                            } else {
                                $net_item_cost = $data['cost'];
                                $item_tax      = 0;
                            }

                            $subtotal = (($net_item_cost * $wh_qty['quantity']) + $item_tax);

                            $item = [
                                'product_id'        => $product_id,
                                'product_code'      => $data['code'],
                                'product_name'      => $data['name'],
                                'net_unit_cost'     => $net_item_cost,
                                'unit_cost'         => $unit_cost,
                                'real_unit_cost'    => $unit_cost,
                                'quantity'          => $wh_qty['quantity'],
                                'quantity_balance'  => $wh_qty['quantity'],
                                'quantity_received' => $wh_qty['quantity'],
                                'item_tax'          => $item_tax,
                                'tax_rate_id'       => $tax_rate_id,
                                'tax'               => $tax,
                                'subtotal'          => $subtotal,
                                'warehouse_id'      => $wh_qty['warehouse_id'],
                                'date'              => date('Y-m-d'),
                                'status'            => 'received',
                            ];
                            $this->db->insert('purchase_items', $item);
                            $this->site->syncProductQty($product_id, $wh_qty['warehouse_id']);
                        }
                    }
                }
            }

            if ($product_attributes) {
                foreach ($product_attributes as $pr_attr) {
                    $pr_attr_details = $this->getPrductVariantByPIDandName($product_id, $pr_attr['name']);

                    $pr_attr['product_id'] = $product_id;
                    $variant_warehouse_id  = $pr_attr['warehouse_id'];
                    unset($pr_attr['warehouse_id']);
                    if ($pr_attr_details) {
                        $option_id = $pr_attr_details->id;
                    } else {
                        $this->db->insert('product_variants', $pr_attr);
                        $option_id = $this->db->insert_id();
                    }
                    if ($pr_attr['quantity'] != 0) {
                        $this->db->insert('warehouses_products_variants', ['option_id' => $option_id, 'product_id' => $product_id, 'warehouse_id' => $variant_warehouse_id, 'quantity' => $pr_attr['quantity']]);

                        $tax_rate_id = $tax_rate ? $tax_rate->id : null;
                        $tax         = $tax_rate ? (($tax_rate->type == 1) ? $tax_rate->rate . '%' : $tax_rate->rate) : null;
                        $unit_cost   = $data['cost'];
                        if ($tax_rate) {
                            if ($tax_rate->type == 1 && $tax_rate->rate != 0) {
                                if ($data['tax_method'] == '0') {
                                    $pr_tax_val    = ($data['cost'] * $tax_rate->rate) / (100 + $tax_rate->rate);
                                    $net_item_cost = $data['cost'] - $pr_tax_val;
                                    $item_tax      = $pr_tax_val * $pr_attr['quantity'];
                                } else {
                                    $net_item_cost = $data['cost'];
                                    $pr_tax_val    = ($data['cost'] * $tax_rate->rate) / 100;
                                    $unit_cost     = $data['cost'] + $pr_tax_val;
                                    $item_tax      = $pr_tax_val * $pr_attr['quantity'];
                                }
                            } else {
                                $net_item_cost = $data['cost'];
                                $item_tax      = $tax_rate->rate;
                            }
                        } else {
                            $net_item_cost = $data['cost'];
                            $item_tax      = 0;
                        }

                        $subtotal = (($net_item_cost * $pr_attr['quantity']) + $item_tax);
                        $item     = [
                            'product_id'        => $product_id,
                            'product_code'      => $data['code'],
                            'product_name'      => $data['name'],
                            'net_unit_cost'     => $net_item_cost,
                            'unit_cost'         => $unit_cost,
                            'quantity'          => $pr_attr['quantity'],
                            'option_id'         => $option_id,
                            'quantity_balance'  => $pr_attr['quantity'],
                            'quantity_received' => $pr_attr['quantity'],
                            'item_tax'          => $item_tax,
                            'tax_rate_id'       => $tax_rate_id,
                            'tax'               => $tax,
                            'subtotal'          => $subtotal,
                            'warehouse_id'      => $variant_warehouse_id,
                            'date'              => date('Y-m-d'),
                            'status'            => 'received',
                        ];
                        $item['option_id'] = !empty($item['option_id']) && is_numeric($item['option_id']) ? $item['option_id'] : null;
                        $this->db->insert('purchase_items', $item);
                    }

                    foreach ($warehouses as $warehouse) {
                        if (!$this->getWarehouseProductVariant($warehouse->id, $product_id, $option_id)) {
                            $this->db->insert('warehouses_products_variants', ['option_id' => $option_id, 'product_id' => $product_id, 'warehouse_id' => $warehouse->id, 'quantity' => 0]);
                        }
                    }

                    $this->site->syncVariantQty($option_id, $variant_warehouse_id);
                }
            }

            if ($photos) {
                foreach ($photos as $photo) {
                    $this->db->insert('product_photos', ['product_id' => $product_id, 'photo' => $photo]);
                }
            }

            $this->site->syncQuantity(null, null, null, $product_id);
            return true;
        }
        return false;
    }

    public function updateProduct($id, $data, $items, $warehouse_qty, $product_attributes, $photos, $update_variants)
    {
        if ($this->db->update('products', $data, ['id' => $id])) {
            if ($items) {
                $this->db->delete('combo_items', ['product_id' => $id]);
                foreach ($items as $item) {
                    $item['product_id'] = $id;
                    $this->db->insert('combo_items', $item);
                }
            }

            $tax_rate = $this->site->getTaxRateByID($data['tax_rate']);

            if ($warehouse_qty && !empty($warehouse_qty)) {
                foreach ($warehouse_qty as $wh_qty) {
                    $this->db->update('warehouses_products', ['rack' => $wh_qty['rack']], ['product_id' => $id, 'warehouse_id' => $wh_qty['warehouse_id']]);
                }
            }

            if (!empty($update_variants)) {
                foreach ($update_variants as $variant) {
                    $vr = $this->getProductVariantByName($id, $variant['name']);
                    if ($vr) {
                        $this->db->update('product_variants', $variant, ['id' => $vr->id]);
                    } else {
                        if ($variant['id']) {
                            $this->db->delete('product_variants', ['id' => $variant['id']]);
                        } else {
                            $this->db->insert('product_variants', $variant);
                        }
                    }
                }
            }

            if ($photos) {
                foreach ($photos as $photo) {
                    $this->db->insert('product_photos', ['product_id' => $id, 'photo' => $photo]);
                }
            }

            if ($product_attributes) {
                foreach ($product_attributes as $pr_attr) {
                    $pr_attr['product_id'] = $id;
                    $variant_warehouse_id  = $pr_attr['warehouse_id'];
                    unset($pr_attr['warehouse_id']);
                    $this->db->insert('product_variants', $pr_attr);
                    $option_id = $this->db->insert_id();

                    if ($pr_attr['quantity'] != 0) {
                        $this->db->insert('warehouses_products_variants', ['option_id' => $option_id, 'product_id' => $id, 'warehouse_id' => $variant_warehouse_id, 'quantity' => $pr_attr['quantity']]);

                        $tax_rate_id = $tax_rate ? $tax_rate->id : null;
                        $tax         = $tax_rate ? (($tax_rate->type == 1) ? $tax_rate->rate . '%' : $tax_rate->rate) : null;
                        $unit_cost   = $data['cost'];
                        if ($tax_rate) {
                            if ($tax_rate->type == 1 && $tax_rate->rate != 0) {
                                if ($data['tax_method'] == '0') {
                                    $pr_tax_val    = ($data['cost'] * $tax_rate->rate) / (100 + $tax_rate->rate);
                                    $net_item_cost = $data['cost'] - $pr_tax_val;
                                    $item_tax      = $pr_tax_val * $pr_attr['quantity'];
                                } else {
                                    $net_item_cost = $data['cost'];
                                    $pr_tax_val    = ($data['cost'] * $tax_rate->rate) / 100;
                                    $unit_cost     = $data['cost'] + $pr_tax_val;
                                    $item_tax      = $pr_tax_val * $pr_attr['quantity'];
                                }
                            } else {
                                $net_item_cost = $data['cost'];
                                $item_tax      = $tax_rate->rate;
                            }
                        } else {
                            $net_item_cost = $data['cost'];
                            $item_tax      = 0;
                        }

                        $subtotal = (($net_item_cost * $pr_attr['quantity']) + $item_tax);
                        $item     = [
                            'product_id'        => $id,
                            'product_code'      => $data['code'],
                            'product_name'      => $data['name'],
                            'net_unit_cost'     => $net_item_cost,
                            'unit_cost'         => $unit_cost,
                            'quantity'          => $pr_attr['quantity'],
                            'option_id'         => $option_id,
                            'quantity_balance'  => $pr_attr['quantity'],
                            'quantity_received' => $pr_attr['quantity'],
                            'item_tax'          => $item_tax,
                            'tax_rate_id'       => $tax_rate_id,
                            'tax'               => $tax,
                            'subtotal'          => $subtotal,
                            'warehouse_id'      => $variant_warehouse_id,
                            'date'              => date('Y-m-d'),
                            'status'            => 'received',
                        ];
                        $item['option_id'] = !empty($item['option_id']) && is_numeric($item['option_id']) ? $item['option_id'] : null;
                        $this->db->insert('purchase_items', $item);
                    }
                }
            }

            $this->site->syncQuantity(null, null, null, $id);
            return true;
        }
        return false;
    }

    public function deleteProduct($id)
    {
        $this->site->log('Product', ['model' => $this->getProductByID($id)]);
        if ($this->db->delete('products', ['id' => $id]) && $this->db->delete('warehouses_products', ['product_id' => $id])) {
            $this->db->delete('warehouses_products_variants', ['product_id' => $id]);
            $this->db->delete('product_variants', ['product_id' => $id]);
            $this->db->delete('product_photos', ['product_id' => $id]);
            $this->db->delete('product_prices', ['product_id' => $id]);
            return true;
        }
        return false;
    }
}
