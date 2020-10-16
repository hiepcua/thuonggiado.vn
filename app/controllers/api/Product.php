<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Product extends CI_Controller
{
	public function __construct() {
		parent::__construct();
		// if (!$this->loggedIn) {
		// 	$this->session->set_userdata('requested_page', $this->uri->uri_string());
		// 	$this->sma->md('login');
		// }

        // $this->load->library('form_validation');
        // $this->load->model('Product_model');

        // $this->load->admin_model('products_model');
        // $this->digital_upload_path = 'files/';
        // $this->upload_path         = 'assets/uploads/';
        // $this->thumbs_path         = 'assets/uploads/thumbs/';
        // $this->image_types         = 'gif|jpg|jpeg|png|tif';
        // $this->digital_file_types  = 'zip|psd|ai|rar|pdf|doc|docx|xls|xlsx|ppt|pptx|gif|jpg|jpeg|png|tif|txt';
        // $this->allowed_file_size   = '1024';
        // $this->popup_attributes    = ['width' => '900', 'height' => '600', 'window_name' => 'sma_popup', 'menubar' => 'yes', 'scrollbars' => 'yes', 'status' => 'no', 'resizable' => 'yes', 'screenx' => '0', 'screeny' => '0'];
	}

	public function add($id = null)
    {
        $this->sma->checkPermissions();
        $this->load->helper('security');
        $warehouses = $this->site->getAllWarehouses();
        $this->form_validation->set_rules('category', lang('category'), 'required|is_natural_no_zero');
        if ($this->input->post('type') == 'standard') {
            $this->form_validation->set_rules('cost', lang('product_cost'), 'required');
            $this->form_validation->set_rules('unit', lang('product_unit'), 'required');
        }
        $this->form_validation->set_rules('code', lang('product_code'), 'is_unique[products.code]|alpha_dash');
        if (SHOP) {
            $this->form_validation->set_rules('slug', lang('slug'), 'required|is_unique[products.slug]|alpha_dash');
        }
        $this->form_validation->set_rules('weight', lang('weight'), 'numeric');
        $this->form_validation->set_rules('product_image', lang('product_image'), 'xss_clean');
        $this->form_validation->set_rules('digital_file', lang('digital_file'), 'xss_clean');
        $this->form_validation->set_rules('userfile', lang('product_gallery_images'), 'xss_clean');
        if ($this->form_validation->run() == true) {
            $tax_rate = $this->input->post('tax_rate') ? $this->site->getTaxRateByID($this->input->post('tax_rate')) : null;
            $data     = [
                'code'              => $this->input->post('code'),
                'barcode_symbology' => $this->input->post('barcode_symbology'),
                'name'              => $this->input->post('name'),
                'type'              => $this->input->post('type'),
                'brand'             => $this->input->post('brand'),
                'category_id'       => $this->input->post('category'),
                'subcategory_id'    => $this->input->post('subcategory') ? $this->input->post('subcategory') : null,
                'cost'              => $this->sma->formatDecimal($this->input->post('cost')),
                'price'             => $this->sma->formatDecimal($this->input->post('price')),
                'unit'              => $this->input->post('unit'),
                'sale_unit'         => $this->input->post('default_sale_unit'),
                'purchase_unit'     => $this->input->post('default_purchase_unit'),
                'tax_rate'          => $this->input->post('tax_rate'),
                'tax_method'        => $this->input->post('tax_method'),
                'alert_quantity'    => $this->input->post('alert_quantity'),
                'track_quantity'    => $this->input->post('track_quantity') ? $this->input->post('track_quantity') : '0',
                'details'           => $this->input->post('details'),
                'product_details'   => $this->input->post('product_details'),
                'supplier1'         => $this->input->post('supplier'),
                'supplier1price'    => $this->sma->formatDecimal($this->input->post('supplier_price')),
                'supplier2'         => $this->input->post('supplier_2'),
                'supplier2price'    => $this->sma->formatDecimal($this->input->post('supplier_2_price')),
                'supplier3'         => $this->input->post('supplier_3'),
                'supplier3price'    => $this->sma->formatDecimal($this->input->post('supplier_3_price')),
                'supplier4'         => $this->input->post('supplier_4'),
                'supplier4price'    => $this->sma->formatDecimal($this->input->post('supplier_4_price')),
                'supplier5'         => $this->input->post('supplier_5'),
                'supplier5price'    => $this->sma->formatDecimal($this->input->post('supplier_5_price')),
                'cf1'               => $this->input->post('cf1'),
                'cf2'               => $this->input->post('cf2'),
                'cf3'               => $this->input->post('cf3'),
                'cf4'               => $this->input->post('cf4'),
                'cf5'               => $this->input->post('cf5'),
                'cf6'               => $this->input->post('cf6'),
                'promotion'         => $this->input->post('promotion'),
                'promo_price'       => $this->sma->formatDecimal($this->input->post('promo_price')),
                'start_date'        => $this->input->post('start_date') ? $this->sma->fsd($this->input->post('start_date')) : null,
                'end_date'          => $this->input->post('end_date') ? $this->sma->fsd($this->input->post('end_date')) : null,
                'supplier1_part_no' => $this->input->post('supplier_part_no'),
                'supplier2_part_no' => $this->input->post('supplier_2_part_no'),
                'supplier3_part_no' => $this->input->post('supplier_3_part_no'),
                'supplier4_part_no' => $this->input->post('supplier_4_part_no'),
                'supplier5_part_no' => $this->input->post('supplier_5_part_no'),
                'file'              => $this->input->post('file_link'),
                'slug'              => $this->input->post('slug'),
                'weight'            => $this->input->post('weight'),
                'featured'          => $this->input->post('featured'),
                'hsn_code'          => $this->input->post('hsn_code'),
                'hide'              => $this->input->post('hide') ? $this->input->post('hide') : 0,
                'second_name'       => $this->input->post('second_name'),
            ];
            $warehouse_qty      = null;
            $product_attributes = null;
            $this->load->library('upload');
            if ($this->input->post('type') == 'standard') {
                $wh_total_quantity = 0;
                $pv_total_quantity = 0;
                for ($s = 2; $s > 5; $s++) {
                    $data['suppliers' . $s]           = $this->input->post('supplier_' . $s);
                    $data['suppliers' . $s . 'price'] = $this->input->post('supplier_' . $s . '_price');
                }
                foreach ($warehouses as $warehouse) {
                    if ($this->input->post('wh_qty_' . $warehouse->id)) {
                        $warehouse_qty[] = [
                            'warehouse_id' => $this->input->post('wh_' . $warehouse->id),
                            'quantity'     => $this->input->post('wh_qty_' . $warehouse->id),
                            'rack'         => $this->input->post('rack_' . $warehouse->id) ? $this->input->post('rack_' . $warehouse->id) : null,
                        ];
                        $wh_total_quantity += $this->input->post('wh_qty_' . $warehouse->id);
                    }
                }

                if ($this->input->post('attributes')) {
                    $a = sizeof($_POST['attr_name']);
                    for ($r = 0; $r <= $a; $r++) {
                        if (isset($_POST['attr_name'][$r])) {
                            $product_attributes[] = [
                                'name'         => $_POST['attr_name'][$r],
                                'warehouse_id' => $_POST['attr_warehouse'][$r],
                                'quantity'     => $_POST['attr_quantity'][$r],
                                'price'        => $_POST['attr_price'][$r],
                            ];
                            $pv_total_quantity += $_POST['attr_quantity'][$r];
                        }
                    }
                } else {
                    $product_attributes = null;
                }

                if ($wh_total_quantity != $pv_total_quantity && $pv_total_quantity != 0) {
                    $this->form_validation->set_rules('wh_pr_qty_issue', 'wh_pr_qty_issue', 'required');
                    $this->form_validation->set_message('required', lang('wh_pr_qty_issue'));
                }
            }

            if ($this->input->post('type') == 'service') {
                $data['track_quantity'] = 0;
            } elseif ($this->input->post('type') == 'combo') {
                $total_price = 0;
                $c           = sizeof($_POST['combo_item_code']) - 1;
                for ($r = 0; $r <= $c; $r++) {
                    if (isset($_POST['combo_item_code'][$r]) && isset($_POST['combo_item_quantity'][$r]) && isset($_POST['combo_item_price'][$r])) {
                        $items[] = [
                            'item_code'  => $_POST['combo_item_code'][$r],
                            'quantity'   => $_POST['combo_item_quantity'][$r],
                            'unit_price' => $_POST['combo_item_price'][$r],
                        ];
                    }
                    $total_price += $_POST['combo_item_price'][$r] * $_POST['combo_item_quantity'][$r];
                }
                if ($this->sma->formatDecimal($total_price) != $this->sma->formatDecimal($this->input->post('price'))) {
                    $this->form_validation->set_rules('combo_price', 'combo_price', 'required');
                    $this->form_validation->set_message('required', lang('pprice_not_match_ciprice'));
                }
                $data['track_quantity'] = 0;
            } elseif ($this->input->post('type') == 'digital') {
                if ($_FILES['digital_file']['size'] > 0) {
                    $config['upload_path']   = $this->digital_upload_path;
                    $config['allowed_types'] = $this->digital_file_types;
                    $config['max_size']      = $this->allowed_file_size;
                    $config['overwrite']     = false;
                    $config['encrypt_name']  = true;
                    $config['max_filename']  = 25;
                    $this->upload->initialize($config);
                    if (!$this->upload->do_upload('digital_file')) {
                        $error = $this->upload->display_errors();
                        $this->session->set_flashdata('error', $error);
                        admin_redirect('products/add');
                    }
                    $file         = $this->upload->file_name;
                    $data['file'] = $file;
                } else {
                    if (!$this->input->post('file_link')) {
                        $this->form_validation->set_rules('digital_file', lang('digital_file'), 'required');
                    }
                }
                $config                 = null;
                $data['track_quantity'] = 0;
            }
            if (!isset($items)) {
                $items = null;
            }
            if ($_FILES['product_image']['size'] > 0) {
                $config['upload_path']   = $this->upload_path;
                $config['allowed_types'] = $this->image_types;
                $config['max_size']      = $this->allowed_file_size;
                $config['max_width']     = $this->Settings->iwidth;
                $config['max_height']    = $this->Settings->iheight;
                $config['overwrite']     = false;
                $config['max_filename']  = 25;
                $config['encrypt_name']  = true;
                $this->upload->initialize($config);
                if (!$this->upload->do_upload('product_image')) {
                    $error = $this->upload->display_errors();
                    $this->session->set_flashdata('error', $error);
                    admin_redirect('products/add');
                }
                $photo         = $this->upload->file_name;
                $data['image'] = $photo;
                $this->load->library('image_lib');
                $config['image_library']  = 'gd2';
                $config['source_image']   = $this->upload_path . $photo;
                $config['new_image']      = $this->thumbs_path . $photo;
                $config['maintain_ratio'] = true;
                $config['width']          = $this->Settings->twidth;
                $config['height']         = $this->Settings->theight;
                $this->image_lib->clear();
                $this->image_lib->initialize($config);
                if (!$this->image_lib->resize()) {
                    echo $this->image_lib->display_errors();
                }
                if ($this->Settings->watermark) {
                    $this->image_lib->clear();
                    $wm['source_image']     = $this->upload_path . $photo;
                    $wm['wm_text']          = 'Copyright ' . date('Y') . ' - ' . $this->Settings->site_name;
                    $wm['wm_type']          = 'text';
                    $wm['wm_font_path']     = 'system/fonts/texb.ttf';
                    $wm['quality']          = '100';
                    $wm['wm_font_size']     = '16';
                    $wm['wm_font_color']    = '999999';
                    $wm['wm_shadow_color']  = 'CCCCCC';
                    $wm['wm_vrt_alignment'] = 'top';
                    $wm['wm_hor_alignment'] = 'left';
                    $wm['wm_padding']       = '10';
                    $this->image_lib->initialize($wm);
                    $this->image_lib->watermark();
                }
                $this->image_lib->clear();
                $config = null;
            }

            if ($_FILES['userfile']['name'][0] != '') {
                $config['upload_path']   = $this->upload_path;
                $config['allowed_types'] = $this->image_types;
                $config['max_size']      = $this->allowed_file_size;
                $config['max_width']     = $this->Settings->iwidth;
                $config['max_height']    = $this->Settings->iheight;
                $config['overwrite']     = false;
                $config['encrypt_name']  = true;
                $config['max_filename']  = 25;
                $files                   = $_FILES;
                $cpt                     = count($_FILES['userfile']['name']);
                for ($i = 0; $i < $cpt; $i++) {
                    $_FILES['userfile']['name']     = $files['userfile']['name'][$i];
                    $_FILES['userfile']['type']     = $files['userfile']['type'][$i];
                    $_FILES['userfile']['tmp_name'] = $files['userfile']['tmp_name'][$i];
                    $_FILES['userfile']['error']    = $files['userfile']['error'][$i];
                    $_FILES['userfile']['size']     = $files['userfile']['size'][$i];

                    $this->upload->initialize($config);

                    if (!$this->upload->do_upload()) {
                        $error = $this->upload->display_errors();
                        $this->session->set_flashdata('error', $error);
                        admin_redirect('products/add');
                    } else {
                        $pho = $this->upload->file_name;

                        $photos[] = $pho;

                        $this->load->library('image_lib');
                        $config['image_library']  = 'gd2';
                        $config['source_image']   = $this->upload_path . $pho;
                        $config['new_image']      = $this->thumbs_path . $pho;
                        $config['maintain_ratio'] = true;
                        $config['width']          = $this->Settings->twidth;
                        $config['height']         = $this->Settings->theight;

                        $this->image_lib->initialize($config);

                        if (!$this->image_lib->resize()) {
                            echo $this->image_lib->display_errors();
                        }

                        if ($this->Settings->watermark) {
                            $this->image_lib->clear();
                            $wm['source_image']     = $this->upload_path . $pho;
                            $wm['wm_text']          = 'Copyright ' . date('Y') . ' - ' . $this->Settings->site_name;
                            $wm['wm_type']          = 'text';
                            $wm['wm_font_path']     = 'system/fonts/texb.ttf';
                            $wm['quality']          = '100';
                            $wm['wm_font_size']     = '16';
                            $wm['wm_font_color']    = '999999';
                            $wm['wm_shadow_color']  = 'CCCCCC';
                            $wm['wm_vrt_alignment'] = 'top';
                            $wm['wm_hor_alignment'] = 'left';
                            $wm['wm_padding']       = '10';
                            $this->image_lib->initialize($wm);
                            $this->image_lib->watermark();
                        }

                        $this->image_lib->clear();
                    }
                }
                $config = null;
            } else {
                $photos = null;
            }
            $data['quantity'] = $wh_total_quantity ?? 0;
            // $this->sma->print_arrays($data, $warehouse_qty, $product_attributes);
        }

        if ($this->form_validation->run() == true && $this->products_model->addProduct($data, $items, $warehouse_qty, $product_attributes, $photos)) {
            $this->session->set_flashdata('message', lang('product_added'));
            admin_redirect('products');
        } else {
            $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));

            $this->data['categories']          = $this->site->getAllCategories();
            $this->data['tax_rates']           = $this->site->getAllTaxRates();
            $this->data['brands']              = $this->site->getAllBrands();
            $this->data['base_units']          = $this->site->getAllBaseUnits();
            $this->data['warehouses']          = $warehouses;
            $this->data['warehouses_products'] = $id ? $this->products_model->getAllWarehousesWithPQ($id) : null;
            $this->data['product']             = $id ? $this->products_model->getProductByID($id) : null;
            $this->data['variants']            = $this->products_model->getAllVariants();
            $this->data['combo_items']         = ($id && $this->data['product']->type == 'combo') ? $this->products_model->getProductComboItems($id) : null;
            $this->data['product_options']     = $id ? $this->products_model->getProductOptionsWithWH($id) : null;
            $bc                                = [['link' => base_url(), 'page' => lang('home')], ['link' => admin_url('products'), 'page' => lang('products')], ['link' => '#', 'page' => lang('add_product')]];
            $meta                              = ['page_title' => lang('add_product'), 'bc' => $bc];
            $this->page_construct('products/add', $meta, $this->data);
        }
    }

    public function edit($id = null)
    {
        $this->sma->checkPermissions();
        $this->load->helper('security');
        if ($this->input->post('id')) {
            $id = $this->input->post('id');
        }
        $warehouses          = $this->site->getAllWarehouses();
        $warehouses_products = $this->products_model->getAllWarehousesWithPQ($id);
        $product             = $this->site->getProductByID($id);
        if (!$id || !$product) {
            $this->session->set_flashdata('error', lang('prduct_not_found'));
            redirect($_SERVER['HTTP_REFERER']);
        }
        $this->form_validation->set_rules('category', lang('category'), 'required|is_natural_no_zero');
        if ($this->input->post('type') == 'standard') {
            $this->form_validation->set_rules('cost', lang('product_cost'), 'required');
            $this->form_validation->set_rules('unit', lang('product_unit'), 'required');
        }
        $this->form_validation->set_rules('code', lang('product_code'), 'alpha_dash');
        if ($this->input->post('code') !== $product->code) {
            $this->form_validation->set_rules('code', lang('product_code'), 'is_unique[products.code]');
        }
        if (SHOP) {
            $this->form_validation->set_rules('slug', lang('slug'), 'required|alpha_dash');
            if ($this->input->post('slug') !== $product->slug) {
                $this->form_validation->set_rules('slug', lang('slug'), 'required|is_unique[products.slug]|alpha_dash');
            }
        }
        $this->form_validation->set_rules('weight', lang('weight'), 'numeric');
        $this->form_validation->set_rules('product_image', lang('product_image'), 'xss_clean');
        $this->form_validation->set_rules('digital_file', lang('digital_file'), 'xss_clean');
        $this->form_validation->set_rules('userfile', lang('product_gallery_images'), 'xss_clean');

        if ($this->form_validation->run('products/add') == true) {
            $data = ['code'         => $this->input->post('code'),
                'barcode_symbology' => $this->input->post('barcode_symbology'),
                'name'              => $this->input->post('name'),
                'type'              => $this->input->post('type'),
                'brand'             => $this->input->post('brand'),
                'category_id'       => $this->input->post('category'),
                'subcategory_id'    => $this->input->post('subcategory') ? $this->input->post('subcategory') : null,
                'cost'              => $this->sma->formatDecimal($this->input->post('cost')),
                'price'             => $this->sma->formatDecimal($this->input->post('price')),
                'unit'              => $this->input->post('unit'),
                'sale_unit'         => $this->input->post('default_sale_unit'),
                'purchase_unit'     => $this->input->post('default_purchase_unit'),
                'tax_rate'          => $this->input->post('tax_rate'),
                'tax_method'        => $this->input->post('tax_method'),
                'alert_quantity'    => $this->input->post('alert_quantity'),
                'track_quantity'    => $this->input->post('track_quantity') ? $this->input->post('track_quantity') : '0',
                'details'           => $this->input->post('details'),
                'product_details'   => $this->input->post('product_details'),
                'supplier1'         => $this->input->post('supplier'),
                'supplier1price'    => $this->sma->formatDecimal($this->input->post('supplier_price')),
                'supplier2'         => $this->input->post('supplier_2'),
                'supplier2price'    => $this->sma->formatDecimal($this->input->post('supplier_2_price')),
                'supplier3'         => $this->input->post('supplier_3'),
                'supplier3price'    => $this->sma->formatDecimal($this->input->post('supplier_3_price')),
                'supplier4'         => $this->input->post('supplier_4'),
                'supplier4price'    => $this->sma->formatDecimal($this->input->post('supplier_4_price')),
                'supplier5'         => $this->input->post('supplier_5'),
                'supplier5price'    => $this->sma->formatDecimal($this->input->post('supplier_5_price')),
                'cf1'               => $this->input->post('cf1'),
                'cf2'               => $this->input->post('cf2'),
                'cf3'               => $this->input->post('cf3'),
                'cf4'               => $this->input->post('cf4'),
                'cf5'               => $this->input->post('cf5'),
                'cf6'               => $this->input->post('cf6'),
                'promotion'         => $this->input->post('promotion'),
                'promo_price'       => $this->sma->formatDecimal($this->input->post('promo_price')),
                'start_date'        => $this->input->post('start_date') ? $this->sma->fsd($this->input->post('start_date')) : null,
                'end_date'          => $this->input->post('end_date') ? $this->sma->fsd($this->input->post('end_date')) : null,
                'supplier1_part_no' => $this->input->post('supplier_part_no'),
                'supplier2_part_no' => $this->input->post('supplier_2_part_no'),
                'supplier3_part_no' => $this->input->post('supplier_3_part_no'),
                'supplier4_part_no' => $this->input->post('supplier_4_part_no'),
                'supplier5_part_no' => $this->input->post('supplier_5_part_no'),
                'slug'              => $this->input->post('slug'),
                'weight'            => $this->input->post('weight'),
                'featured'          => $this->input->post('featured'),
                'hsn_code'          => $this->input->post('hsn_code'),
                'hide'              => $this->input->post('hide') ? $this->input->post('hide') : 0,
                'hide_pos'          => $this->input->post('hide_pos') ? $this->input->post('hide_pos') : 0,
                'second_name'       => $this->input->post('second_name'),
            ];
            $warehouse_qty      = null;
            $product_attributes = null;
            $update_variants    = [];
            $this->load->library('upload');
            if ($this->input->post('type') == 'standard') {
                if ($product_variants = $this->products_model->getProductOptions($id)) {
                    foreach ($product_variants as $pv) {
                        $update_variants[] = [
                            'id'    => $this->input->post('variant_id_' . $pv->id),
                            'name'  => $this->input->post('variant_name_' . $pv->id),
                            'cost'  => $this->input->post('variant_cost_' . $pv->id),
                            'price' => $this->input->post('variant_price_' . $pv->id),
                        ];
                    }
                }
                for ($s = 2; $s > 5; $s++) {
                    $data['suppliers' . $s]           = $this->input->post('supplier_' . $s);
                    $data['suppliers' . $s . 'price'] = $this->input->post('supplier_' . $s . '_price');
                }
                foreach ($warehouses as $warehouse) {
                    $warehouse_qty[] = [
                        'warehouse_id' => $this->input->post('wh_' . $warehouse->id),
                        'rack'         => $this->input->post('rack_' . $warehouse->id) ? $this->input->post('rack_' . $warehouse->id) : null,
                    ];
                }

                if ($this->input->post('attributes')) {
                    $a = sizeof($_POST['attr_name']);
                    for ($r = 0; $r <= $a; $r++) {
                        if (isset($_POST['attr_name'][$r])) {
                            if ($product_variatnt = $this->products_model->getPrductVariantByPIDandName($id, trim($_POST['attr_name'][$r]))) {
                                $this->form_validation->set_message('required', lang('product_already_has_variant') . ' (' . $_POST['attr_name'][$r] . ')');
                                $this->form_validation->set_rules('new_product_variant', lang('new_product_variant'), 'required');
                            } else {
                                $product_attributes[] = [
                                    'name'         => $_POST['attr_name'][$r],
                                    'warehouse_id' => $_POST['attr_warehouse'][$r],
                                    'quantity'     => $_POST['attr_quantity'][$r],
                                    'price'        => $_POST['attr_price'][$r],
                                ];
                            }
                        }
                    }
                } else {
                    $product_attributes = null;
                }
            }

            if ($this->input->post('type') == 'service') {
                $data['track_quantity'] = 0;
            } elseif ($this->input->post('type') == 'combo') {
                $total_price = 0;
                $c           = sizeof($_POST['combo_item_code']) - 1;
                for ($r = 0; $r <= $c; $r++) {
                    if (isset($_POST['combo_item_code'][$r]) && isset($_POST['combo_item_quantity'][$r]) && isset($_POST['combo_item_price'][$r])) {
                        $items[] = [
                            'item_code'  => $_POST['combo_item_code'][$r],
                            'quantity'   => $_POST['combo_item_quantity'][$r],
                            'unit_price' => $_POST['combo_item_price'][$r],
                        ];
                    }
                    $total_price += $_POST['combo_item_price'][$r] * $_POST['combo_item_quantity'][$r];
                }
                if ($this->sma->formatDecimal($total_price) != $this->sma->formatDecimal($this->input->post('price'))) {
                    $this->form_validation->set_rules('combo_price', 'combo_price', 'required');
                    $this->form_validation->set_message('required', lang('pprice_not_match_ciprice'));
                }
                $data['track_quantity'] = 0;
            } elseif ($this->input->post('type') == 'digital') {
                if ($this->input->post('file_link')) {
                    $data['file'] = $this->input->post('file_link');
                }
                if ($_FILES['digital_file']['size'] > 0) {
                    $config['upload_path']   = $this->digital_upload_path;
                    $config['allowed_types'] = $this->digital_file_types;
                    $config['max_size']      = $this->allowed_file_size;
                    $config['overwrite']     = false;
                    $config['encrypt_name']  = true;
                    $config['max_filename']  = 25;
                    $this->upload->initialize($config);
                    if (!$this->upload->do_upload('digital_file')) {
                        $error = $this->upload->display_errors();
                        $this->session->set_flashdata('error', $error);
                        admin_redirect('products/add');
                    }
                    $file         = $this->upload->file_name;
                    $data['file'] = $file;
                }
                $config                 = null;
                $data['track_quantity'] = 0;
            }
            if (!isset($items)) {
                $items = null;
            }
            if ($_FILES['product_image']['size'] > 0) {
                $config['upload_path']   = $this->upload_path;
                $config['allowed_types'] = $this->image_types;
                $config['max_size']      = $this->allowed_file_size;
                $config['max_width']     = $this->Settings->iwidth;
                $config['max_height']    = $this->Settings->iheight;
                $config['overwrite']     = false;
                $config['encrypt_name']  = true;
                $config['max_filename']  = 25;
                $this->upload->initialize($config);
                if (!$this->upload->do_upload('product_image')) {
                    $error = $this->upload->display_errors();
                    $this->session->set_flashdata('error', $error);
                    admin_redirect('products/edit/' . $id);
                }
                $photo         = $this->upload->file_name;
                $data['image'] = $photo;
                $this->load->library('image_lib');
                $config['image_library']  = 'gd2';
                $config['source_image']   = $this->upload_path . $photo;
                $config['new_image']      = $this->thumbs_path . $photo;
                $config['maintain_ratio'] = true;
                $config['width']          = $this->Settings->twidth;
                $config['height']         = $this->Settings->theight;
                $this->image_lib->clear();
                $this->image_lib->initialize($config);
                if (!$this->image_lib->resize()) {
                    echo $this->image_lib->display_errors();
                }
                if ($this->Settings->watermark) {
                    $this->image_lib->clear();
                    $wm['source_image']     = $this->upload_path . $photo;
                    $wm['wm_text']          = 'Copyright ' . date('Y') . ' - ' . $this->Settings->site_name;
                    $wm['wm_type']          = 'text';
                    $wm['wm_font_path']     = 'system/fonts/texb.ttf';
                    $wm['quality']          = '100';
                    $wm['wm_font_size']     = '16';
                    $wm['wm_font_color']    = '999999';
                    $wm['wm_shadow_color']  = 'CCCCCC';
                    $wm['wm_vrt_alignment'] = 'top';
                    $wm['wm_hor_alignment'] = 'left';
                    $wm['wm_padding']       = '10';
                    $this->image_lib->initialize($wm);
                    $this->image_lib->watermark();
                }
                $this->image_lib->clear();
                $config = null;
            }

            if ($_FILES['userfile']['name'][0] != '') {
                $config['upload_path']   = $this->upload_path;
                $config['allowed_types'] = $this->image_types;
                $config['max_size']      = $this->allowed_file_size;
                $config['max_width']     = $this->Settings->iwidth;
                $config['max_height']    = $this->Settings->iheight;
                $config['overwrite']     = false;
                $config['encrypt_name']  = true;
                $config['max_filename']  = 25;
                $files                   = $_FILES;
                $cpt                     = count($_FILES['userfile']['name']);
                for ($i = 0; $i < $cpt; $i++) {
                    $_FILES['userfile']['name']     = $files['userfile']['name'][$i];
                    $_FILES['userfile']['type']     = $files['userfile']['type'][$i];
                    $_FILES['userfile']['tmp_name'] = $files['userfile']['tmp_name'][$i];
                    $_FILES['userfile']['error']    = $files['userfile']['error'][$i];
                    $_FILES['userfile']['size']     = $files['userfile']['size'][$i];

                    $this->upload->initialize($config);

                    if (!$this->upload->do_upload()) {
                        $error = $this->upload->display_errors();
                        $this->session->set_flashdata('error', $error);
                        admin_redirect('products/edit/' . $id);
                    } else {
                        $pho = $this->upload->file_name;

                        $photos[] = $pho;

                        $this->load->library('image_lib');
                        $config['image_library']  = 'gd2';
                        $config['source_image']   = $this->upload_path . $pho;
                        $config['new_image']      = $this->thumbs_path . $pho;
                        $config['maintain_ratio'] = true;
                        $config['width']          = $this->Settings->twidth;
                        $config['height']         = $this->Settings->theight;

                        $this->image_lib->initialize($config);

                        if (!$this->image_lib->resize()) {
                            echo $this->image_lib->display_errors();
                        }

                        if ($this->Settings->watermark) {
                            $this->image_lib->clear();
                            $wm['source_image']     = $this->upload_path . $pho;
                            $wm['wm_text']          = 'Copyright ' . date('Y') . ' - ' . $this->Settings->site_name;
                            $wm['wm_type']          = 'text';
                            $wm['wm_font_path']     = 'system/fonts/texb.ttf';
                            $wm['quality']          = '100';
                            $wm['wm_font_size']     = '16';
                            $wm['wm_font_color']    = '999999';
                            $wm['wm_shadow_color']  = 'CCCCCC';
                            $wm['wm_vrt_alignment'] = 'top';
                            $wm['wm_hor_alignment'] = 'left';
                            $wm['wm_padding']       = '10';
                            $this->image_lib->initialize($wm);
                            $this->image_lib->watermark();
                        }

                        $this->image_lib->clear();
                    }
                }
                $config = null;
            } else {
                $photos = null;
            }
            $data['quantity'] = $wh_total_quantity ?? 0;
            // $this->sma->print_arrays($data, $warehouse_qty, $update_variants, $product_attributes, $photos, $items);
        }

        if ($this->form_validation->run() == true && $this->products_model->updateProduct($id, $data, $items, $warehouse_qty, $product_attributes, $photos, $update_variants)) {
            $this->session->set_flashdata('message', lang('product_updated'));
            admin_redirect('products');
        } else {
            $this->data['error'] = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));

            $this->data['categories']          = $this->site->getAllCategories();
            $this->data['tax_rates']           = $this->site->getAllTaxRates();
            $this->data['brands']              = $this->site->getAllBrands();
            $this->data['base_units']          = $this->site->getAllBaseUnits();
            $this->data['warehouses']          = $warehouses;
            $this->data['warehouses_products'] = $warehouses_products;
            $this->data['product']             = $product;
            $this->data['variants']            = $this->products_model->getAllVariants();
            $this->data['subunits']            = $this->site->getUnitsByBUID($product->unit);
            $this->data['product_variants']    = $this->products_model->getProductOptions($id);
            $this->data['combo_items']         = $product->type == 'combo' ? $this->products_model->getProductComboItems($product->id) : null;
            $this->data['product_options']     = $id ? $this->products_model->getProductOptionsWithWH($id) : null;
            $bc                                = [['link' => base_url(), 'page' => lang('home')], ['link' => admin_url('products'), 'page' => lang('products')], ['link' => '#', 'page' => lang('edit_product')]];
            $meta                              = ['page_title' => lang('edit_product'), 'bc' => $bc];
            $this->page_construct('products/edit', $meta, $this->data);
        }
    }

	public function delete($id = null)
    {
        $this->sma->checkPermissions(null, true);

        if ($this->input->get('id')) {
            $id = $this->input->get('id');
        }

        if ($this->products_model->deleteProduct($id)) {
            if ($this->input->is_ajax_request()) {
                $this->sma->send_json(['error' => 0, 'msg' => lang('product_deleted')]);
            }
            $this->session->set_flashdata('message', lang('product_deleted'));
            admin_redirect('welcome');
        }
    }
}