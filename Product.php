<?php

namespace App\Controllers\Admin;

use App\Controllers\Shared\SharedController;
use App\Models\Common\Common_model;
use App\Models\Dashboard\Dashbaord_model;
use App\Models\Product\Product_model;
use App\Models\Reports\Reports_model;
use DateTime;
use stdClass;

class Product extends SharedController
{
    public function __construct()
    {
        parent::__construct();
        $this->Common_model = new Common_model();
        $this->Product_model = new Product_model();
        $this->Dashboard_model = new Dashbaord_model();
        $this->Report_model = new Reports_model();
    }
    private $globalUplineMember = [];

    public function gstMasterReports()
    {
        if ($this->isAdmin() || $this->isSuperAdmin()) {
            # code...
            $data = array();
            $data = [
                'currentPage' => '/wp-dashboard/gst-types/gst-master-reports',
                'pageName' => 'GST Master Reports',
            ];
            if ($this->request->getGet('apply_filter') === 'true') {
                # code...
                $formDate =  $this->request->getGet('form_date');
                $toDate =  $this->request->getGet('to_date');
                $hsncode =  $this->request->getGet('hsn_code');
                if ($toDate < $formDate) {
                    # code...
                    $this->setSessionNotification('wp_page', true, 'error', 'Invalid Date Range is Given in Input');
                    return $this->redirectToUrl('/wp-dashboard/gst-types/gst-master-reports');
                } else {
                    $data['hsncode'] = $this->Product_model->filterMasterGST($formDate, $toDate, $hsncode);
                }
            } else {

                $data['hsncode'] = $this->Product_model->getAllHSNCode();
            }
            return  $this->adminView('gst-master-reports', 'gst-types', $data);
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function isHSNCodeExit($code = '')
    {
        if ($this->checkSessionStatus()) {
            # code...
            return $this->showAJAXReq(true, 'Fetched successfully', $this->Product_model->getGSTByHSNcode($code));
        } else {
            return $this->showAJAXReq(false, 'You have entered wrong domain.', []);
        }
    }

    public function createGstMaster()
    {
        if ($this->isAdmin() || $this->isSuperAdmin()) {
            $hsncode = $this->request->getPost('hsn_code');
            $updateId = $this->request->getPost('master_id');
            $data = [
                'cgst' => $this->request->getPost('default_cgst'),
                'sgst' => $this->request->getPost('default_sgst'),
                'igst' => $this->request->getPost('default_igst'),
                'created_date' => date('Y-m-d H:i:s')
            ];
            if (empty($this->Product_model->getGSTByHSNcode($hsncode)) && !$updateId) {
                # code...
                $data['hsn_code'] = $hsncode;
                $this->Product_model->addNewGstMaster($data);
                $this->setSessionNotification('wp_page', true, 'success', 'The HSN code is added successfully.');
            } elseif ($updateId) {
                # code...
                $this->Product_model->updateGstMasterData($data, $updateId);
                $this->setSessionNotification('wp_page', true, 'success', 'The HSN code is updated successfully.');
            } else {
                $this->setSessionNotification('wp_page', false, 'error', 'The HSN code is already exit. Please try again with a new Code.');
            }
            return $this->redirectToUrl('/wp-dashboard/gst-types/gst-master-reports');
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function loadAllCategory()
    {
        if ($this->isSuperAdmin() || $this->isSuperFrnachise() || $this->isAdmin() || $this->isFranchise()) {
            # code...
            $categoryList = [];
            $isFilter = $this->request->getGet('apply_filter');
            if ($isFilter === 'true') {
                # code...
                $formDate =  $this->request->getGet('form_date');
                $toDate =  $this->request->getGet('to_date');
                $subCat =  $this->request->getGet('category_name');
                if ($formDate > $toDate) {
                    # code...
                    $this->setSessionNotification('wp_page', true, 'error', 'Invalid Date Range is Given in Input');
                } else {
                    $categoryList = $this->Product_model->filterCategory($formDate, $toDate, $subCat);
                    // print_r($categoryList); exit;
                }
            } else {
                $categoryList = $this->Product_model->getAllCategory();
            }
            if (!empty($categoryList)) {
                # code...

                $categoryList = array_map(function ($value) {
                    $value->subcategory_list = empty($this->Product_model->getSubcategoryList($value->id)) ? 0 : count($this->Product_model->getSubcategoryList($value->id));
                    return $value;
                }, $categoryList);
            }
            $data = [
                'currentPage' => '/wp-dashboard/products/product-category',
                'pageName' => 'Product Category',
                'category_list' => $categoryList
            ];
            return  $this->adminView('product-category', 'Products', $data);
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function createCategory()
    {
        if ($this->isAdmin() || $this->isSuperAdmin()) {
            # code...
            $categoryName = $this->request->getPost('category_name');
            $catId =  $this->request->getPost('cat_id');
            if (!empty($this->Product_model->getCategory('', $categoryName))) {
                $this->setSessionNotification('wp_page', false, 'error', 'The Category is already exit. Please try again with a New Category .');
            } else {
                # code...
                if ($catId) {
                    # code...
                    $data = [
                        'category_name' => $categoryName
                    ];
                    $this->setSessionNotification('wp_page', true, 'success', 'Category Updated successfully.');
                    $this->Product_model->updateCategoryStatus($data, $catId);
                } else {
                    $data = [
                        'category_name' => $categoryName,
                        'created_date'  => date('Y-m-d H:i:s')
                    ];
                    $this->Product_model->insertCategory($data);
                    $this->setSessionNotification('wp_page', true, 'success', 'The Category is added successfully.');
                }
            }
            return $this->redirectToUrl('wp-dashboard/products/categories');
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function toggleCategoryStatus($id = '', $status = '1')
    {
        if ($this->isAdmin() || $this->isSuperAdmin()) {
            # code...
            if (!empty($this->Product_model->getCategory($id))) {
                $data = [
                    'status' => $status
                ];
                $this->setSessionNotification('wp_page', true, 'success', 'Status Updated successfully.');
                $this->Product_model->updateCategoryStatus($data, $id);
            } else {
                $this->setSessionNotification('wp_page', false, 'error', 'Something went wrong. Please try again later.');
            }
            return $this->redirectToUrl('wp-dashboard/products/categories');
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function creatSubCat()
    {
        if ($this->isAdmin() || $this->isSuperAdmin()) {
            $catId = $this->request->getPost('category_id') ? $this->request->getPost('category_id') : $this->request->getPost('scat_id');
            $subcateName  = $this->request->getPost('sub_category_name');
            if (!empty($this->Product_model->getSubcategoryList($catId, $subcateName))) {
                $this->setSessionNotification('wp_page', false, 'error', 'Subcategory name already exists in this category.');
            } else {
                $data = [
                    'cat_id' => $catId,
                    'subcat_name' => $subcateName,
                    'created_date'  => date('Y-m-d H:i:s')
                ];
                $this->setSessionNotification('wp_page', true, 'success', 'The Subcategory is added successfully with the associated category.');
                $this->Product_model->insertSubcategory($data);
            }
            return $this->redirectToUrl('wp-dashboard/products/categories');
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function showSubCategory($from = '', $id = '')
    {
        if ($this->isSuperAdmin() || $this->isSuperFrnachise() || $this->isAdmin() || $this->isFranchise()) {
            if (!empty($this->Product_model->getCategory($id))) {
                $subcatList = [];
                $isFilter = $this->request->getGet('apply_filter');
                if ($isFilter === 'true') {
                    # code...
                    $formDate =  $this->request->getGet('form_date');
                    $toDate =  $this->request->getGet('to_date');
                    $subCat =  $this->request->getGet('sub_cat');
                    if ($formDate > $toDate) {
                        # code...
                        $this->setSessionNotification('wp_page', true, 'error', 'Invalid Date Range is Given in Input');
                    } else {
                        $subcatList = $this->Product_model->filterSubCat($formDate, $toDate, $subCat);
                        // echo $subcatList; exit;
                    }
                } else {
                    $subcatList = $this->Product_model->getSubcategoryList($id);
                }
                $data = [
                    'currentPage' => '/wp-dashboard/products/' . $from,
                    'pageName' => 'Subcategory  List',
                    'subcat_list' => $subcatList,
                    'category_list' => $this->Product_model->getCategory($id)[0]
                ];
                return $this->adminView('product-subcategory', 'Products', $data);
            }
            $this->setSessionNotification('wp_page', false, 'error', 'No category found. Pleaset try again!');
            return $this->redirectToUrl('wp-dashboard/products/categories');
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function toggleSubCategoryStatus($id = '', $status = '1')
    {
        if ($this->isAdmin() || $this->isSuperAdmin()) {
            # code...
            if (!empty($this->Product_model->getSubcategoryById($id))) {
                $data = [
                    'status' => $status
                ];
                $this->setSessionNotification('wp_page', true, 'success', 'Status Updated successfully.');
                $this->Product_model->updateSubcategory($data, $id);
                return $this->redirectToUrl('wp-dashboard/products/sub-categories/categories/' . $this->Product_model->getSubcategoryById($id)[0]->cat_id);
            } else {
                $this->setSessionNotification('wp_page', false, 'error', 'Something went wrong. Please try again later.');
                return $this->redirectToUrl('wp-dashboard/products/categories');
            }
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function updateAddSubCat()
    {
        if ($this->isAdmin() || $this->isSuperAdmin()) {
            $catId = $this->request->getPost('cat_id');
            $subcateName  = $this->request->getPost('category_name');
            $subCatId = $this->request->getPost('sub_cat_id');
            if (!empty($this->Product_model->getSubcategoryList($catId, $subcateName))) {
                $this->setSessionNotification('wp_page', false, 'error', 'Subcategory name already exists in this category.');
            } else {
                $data = [
                    'cat_id' => $catId,
                    'subcat_name' => $subcateName,
                    'created_date'  => date('Y-m-d H:i:s')
                ];
                if ($subCatId) {
                    # code...
                    $this->Product_model->updateSubcategory($data, $subCatId);
                    $this->setSessionNotification('wp_page', true, 'success', 'The Subcategory is updated successfully with the associated category.');
                } else {
                    $this->Product_model->insertSubcategory($data);
                    $this->setSessionNotification('wp_page', true, 'success', 'The Subcategory is added successfully with the associated category.');
                }
            }
            return $this->redirectToUrl('wp-dashboard/products/sub-categories/categories/' . $catId);
        }
        $this->setSessionExpiredNotification();
        return $this->redirectToUrl('home');
    }

    public function loadProductList()
    {
        if ($this->checkSessionStatus()) {
            # code...
            $productList = [];
            if ($this->request->getGet('apply_filter') === 'true') {
                # code...
                $formDate =  $this->request->getGet('form_date');
                $toDate =  $this->request->getGet('to_date');
                $categoryName = $this->request->getGet('category_name');
                $status = $this->request->getGet('status');
                if ($toDate < $formDate) {
                    # code...
                    $this->setSessionNotification('wp_page', true, 'error', 'Invalid Date Range is Given in Input');
                    return $this->redirectToUrl('/wp-dashboard/products/product-list');
                } else {
                    $productList = $this->Product_model->filterProductList($toDate, $formDate, $categoryName, $status);
                }
            } else {
                $productList = $this->Product_model->getAllProductList();
            }
            $loadCategory = $this->Product_model->getAllCategory();
            if (!empty($loadCategory)) {
                # code...
                $loadCategory = array_map(function ($value) {
                    $value->subcategory_list = $this->Product_model->getSubcategoryList($value->id);
                    $value->total_stocks = $this->Product_model->loadInventoryByProductId($value->id);
                    return $value;
                }, $loadCategory);
            }
            if (!empty($productList)) {
                # code...
                $productList = array_map(function ($value) {
                    $value->category_name = $this->Product_model->getSubcategoryById($value->category_id);
                    $value->hsn_code = $this->Product_model->getGSTByHSNcodeById($value->hsn_code);
                    $value->total_stocks = $this->Product_model->loadInventoryByProductId($value->id);
                    $value->purchase_price = $this->Product_model->getPurchasePriceByProductId($value->id);
                    return $value;
                }, $productList);
            }
            $data = [
                'currentPage' => '/wp-dashboard/products/product-list',
                'pageName' => 'Product List',
                'hsn_code_list' => $this->Product_model->getAllHSNCode(),
                'category_list' => $loadCategory,
                'product_list' => $productList
            ];
            // echo "<pre>";
            // print_r($data);
            // echo "</pre>";
            // exit;
            return $this->adminView('product-list', 'Products', $data);
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function createProduct()
    {
        if ($this->isAdmin() || $this->isSuperAdmin()) {
            $productSKU = $this->request->getPost('product_code');
            if (empty($this->Product_model->getProductDetails('', $productSKU))) {
                $uploadDir = WRITEPATH . 'uploads/products';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, TRUE);
                }
                $productImage = $this->request->getFile('upload_file');
                $newName = '';
                if ($productImage->isValid() && !$productImage->hasMoved()) {
                    $newName = $productImage->getRandomName();
                    $productImage->move($uploadDir, $newName);
                }
                $data = [
                    'product_name'  => $this->request->getPost('product_name'),
                    'product_sku'   => $productSKU,
                    'product_image' => $newName,
                    'category_id'   => $this->request->getPost('category_id'),
                    'hsn_code'      => $this->request->getPost('hsn_code'),
                    'mrp'           => $this->request->getPost('mrp'),
                    'dp'            => $this->request->getPost('dp'),
                    'bv_bonous'     => $this->request->getPost('bv_bonous'),
                    'comments'      => htmlspecialchars($this->request->getPost('comments')),
                    'created_date'  => date('Y-m-d H:i:s')
                ];
                $statusId = $this->Product_model->insertProductDetails($data);
                $inventory = [
                    'product_id' => $statusId,
                    'sku' => $productSKU,
                    'stocks' => $this->request->getPost('open_stock'),
                    'created_date'  => date('Y-m-d H:i:s')
                ];
                $status = $this->Product_model->updateInventory($inventory);
                if ($status) {
                    # code...
                    $history = [
                        'product_id'    => $statusId,
                        'added_stocks'  => $this->request->getPost('open_stock'),
                        'created_date'  => date('Y-m-d H:i:s')
                    ];
                    $this->Product_model->insertInventoryHistory($history);
                    $this->setSessionNotification('wp_page', true, 'success', 'Product Inserted successfully.');
                } else {
                    $this->setSessionNotification('wp_page', false, 'success', 'There are some error while inserting data. Please try again later .');
                }
            } else {
                $this->setSessionNotification('wp_page', false, 'error', 'Product SKU already Exit.');
            }
            return $this->redirectToUrl('wp-dashboard/products/product-list');
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function changeProductStatus($id = '', $status = '1')
    {
        if ($this->isAdmin() || $this->isSuperAdmin()) {
            if (!empty($this->Product_model->getProductDetails($id))) {
                # code...
                $data = [
                    'status' => $status
                ];
                $this->Product_model->updateProductStatus($id, $data);
                $this->setSessionNotification('wp_page', true, 'success', 'Product Status updated successfully.');
            } else {
                $this->setSessionNotification('wp_page', false, 'error', 'Invalid details given. please try again later.');
            }
            return $this->redirectToUrl('wp-dashboard/products/product-list');
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function loadInventory($productId = '')
    {
        if ($this->isAdmin() || $this->isSuperAdmin()) {
            if (!empty($this->Product_model->getProductDetails($productId))) {
                return $this->showAJAXReq(true, 'Data fetced successfully.', $this->Product_model->loadInventoryByProductId($productId));
            } else {
                return $this->showAJAXReq(false, 'Invalid List given. Please try again later.', []);
            }
        } else {
            return $this->showAJAXReq(false, 'Session expired', []);
        }
    }

    public function updateInventory()
    {
        if ($this->isAdmin() || $this->isSuperAdmin()) {
            $totalStocks = $this->request->getPost('total_stocks');
            $data = [
                'product_id' => $this->request->getPost('product_id'),
                'stocks' => $totalStocks,
            ];
            $this->Product_model->updateInventory($data);
            $history = [
                'product_id'    => $this->request->getPost('product_id'),
                'inventory_status' => $this->request->getPost('stock_type'),
                'added_stocks'  => $this->request->getPost('inventory_number'),
                'remove_reason' => $this->request->getPost('reason_remove'),
                'created_date'  => date('Y-m-d H:i:s')
            ];
            $this->Product_model->insertInventoryHistory($history);
            $this->setSessionNotification('wp_page', true, 'success', 'Product Inventory updated successfully.');
            return $this->redirectToUrl('wp-dashboard/products/product-list');
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function editProductList($id = '')
    {
        if ($this->isAdmin() || $this->isSuperAdmin()) {
            if (!empty($this->Product_model->getProductDetails($id))) {
                $productDetails = $this->Product_model->getProductDetails($id);
                $loadCategory = $this->Product_model->getAllCategory();
                if (!empty($loadCategory)) {
                    # code...
                    $loadCategory = array_map(function ($value) {
                        $value->subcategory_list = $this->Product_model->getSubcategoryList($value->id);
                        return $value;
                    }, $loadCategory);
                }
                if (!empty($productDetails)) {
                    # code...
                    $productDetails = array_map(function ($value) {
                        $value->category_name = $this->Product_model->getCategory($value->category_id);
                        return $value;
                    }, $productDetails);
                }
                $data = [
                    'currentPage' => '/wp-dashboard/products/product-list',
                    'pageName' => 'Product List',
                    'hsn_code_list' => $this->Product_model->getAllHSNCode(),
                    'category_list' => $loadCategory,
                    'product_list' => $productDetails[0]
                ];
                return $this->adminView('product-details', 'Products', $data);
            } else {
                $this->setSessionNotification('wp_page', false, 'error', 'Invalid details given. please try again later.');
                return $this->redirectToUrl('wp-dashboard/products/product-list');
            }
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function updateProductImage()
    {
        if ($this->isAdmin() || $this->isSuperAdmin()) {
            $productId = $this->request->getPost('product_id');
            if (!empty($this->Product_model->getProductDetails($productId))) {
                $productDetails = $this->Product_model->getProductDetails($productId);
                $uploadDir = WRITEPATH . 'uploads/products';
                $frontFile = WRITEPATH . 'uploads/products/' . $productDetails[0]->product_image;
                if (file_exists($frontFile)) {
                    # code...
                    unlink($frontFile);
                }
                $productImage = $this->request->getFile('update');
                $newName = '';
                if ($productImage->isValid() && !$productImage->hasMoved()) {
                    $newName = $productImage->getRandomName();
                    $productImage->move($uploadDir, $newName);
                }
                $data = [
                    'product_image' => $newName
                ];
                $this->Product_model->updateProductStatus($productId, $data);
                return $this->showAJAXReq(true, 'Data updated successfully', ['updated_path' => WEBSITE . '/writable/uploads/products/' . $newName]);
            }
        } else {
            return $this->showAJAXReq(false, 'Session expired', []);
        }
    }

    public function updateProductDetails()
    {
        if ($this->isAdmin() || $this->isSuperAdmin()) {
            $productId = $this->request->getPost('product_id');
            if (!empty($this->Product_model->getProductDetails($productId))) {
                $data = [
                    'product_name' => $this->request->getPost('product_name'),
                    'mrp' => $this->request->getPost('mrp'),
                    'dp' => $this->request->getPost('dp'),
                    'bv_bonous' => $this->request->getPost('bv_bonous'),
                    'comments' => $this->request->getPost('comments')
                ];

                $this->Product_model->updateProductStatus($productId, $data);
                $this->setSessionNotification('wp_page', true, 'success', 'Product Updated Successfully');
            } else {
                $this->setSessionNotification('wp_page', false, 'error', 'Invalid details given. please try again later.');
            }
            return $this->redirectToUrl('wp-dashboard/products/product-list');
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function packageList()
    {
        if ($this->isAdmin() || $this->isSuperAdmin()) {
            $loadCategory = $this->Product_model->getAllCategory();
            $packageList = [];
            if ($this->request->getGet('apply_filter') === 'true') {
                # code...
                $formDate =  $this->request->getGet('form_date');
                $toDate =  $this->request->getGet('to_date');
                $categoryName = $this->request->getGet('category_name');
                $status = $this->request->getGet('status');
                if ($toDate < $formDate) {
                    # code...
                    $this->setSessionNotification('wp_page', true, 'error', 'Invalid Date Range is Given in Input');
                    return $this->redirectToUrl('/wp-dashboard/products/package-list');
                } else {
                    $packageList = $this->Product_model->filterPackgeList($toDate, $formDate, $categoryName, $status);
                }
            } else {
                $packageList = $this->Product_model->getAllPackageList();
            }
            if (!empty($loadCategory)) {
                # code...
                $loadCategory = array_map(function ($value) {
                    $value->subcategory_list = $this->Product_model->getSubcategoryList($value->id);
                    return $value;
                }, $loadCategory);
            }

            if (!empty($packageList)) {
                # code...
                $packageList = array_map(function ($value) {
                    $value->category_name = $this->Product_model->getSubcategoryById($value->cat_id);
                    $value->stocks_list = $this->Product_model->loadPackageInventory($value->id);
                    $value->package_product = $this->Product_model->loadPackageProduct($value->id);
                    $value->purchase_price = $this->Product_model->getPurchasePriceByPackageId($value->id);
                    return $value;
                }, $packageList);
            }
            $data = [
                'currentPage' => '/wp-dashboard/products/package-list',
                'pageName' => 'Package List',
                'hsn_code_list' => $this->Product_model->getAllHSNCode(),
                'category_list' => $loadCategory,
                'package_list' => $packageList
            ];
            return $this->adminView('product-package-list', 'Products', $data);
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function getActiveProduct()
    {
        if ($this->checkSessionStatus()) {
            $productList = $this->Product_model->getActiveProduct();
            if (!empty($productList)) {
                # code...
                $isEditActive = $this->request->getGet('fromEdit');
                if (!$isEditActive) {
                    # code...

                    $productList = array_filter($productList, function ($value) {
                        $stockQty = $this->Product_model->loadInventoryByProductId($value->id);
                        if (!empty($stockQty) && (int)$stockQty[0]->stocks > 0) {
                            # code...
                            $value->stocks = $stockQty[0]->stocks;
                            return $value;
                        }
                    });
                } else {

                    $productList = array_filter($productList, function ($value) {
                        $stockQty = $this->Product_model->loadInventoryByProductId($value->id);
                        if (!empty($stockQty)) {
                            # code...
                            $value->stocks = $stockQty[0]->stocks;
                            return $value;
                        }
                    });
                }
            }
            return $this->showAJAXReq(true, 'Data fetched successfully', array_values($productList));
        } else {
            return $this->showAJAXReq(false, 'Session expired', []);
        }
    }

    public function createProductPackage()
    {
        if ($this->isAdmin() || $this->isSuperAdmin()) {
            # code...
            $packageCode = $this->request->getPost('package_code');
            if (empty($this->Product_model->getPackageDetails('', $packageCode))) {
                $uploadDir = WRITEPATH . 'uploads/packages';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, TRUE);
                }
                $productImage = $this->request->getFile('upload_file');
                $newName = '';
                if ($productImage->isValid() && !$productImage->hasMoved()) {
                    $newName = $productImage->getRandomName();
                    $productImage->move($uploadDir, $newName);
                }
                $data = [
                    'package_name'  => $this->request->getPost('package_name'),
                    'package_code'   => $packageCode,
                    'cat_id'   => $this->request->getPost('category_id'),
                    'mrp'           => $this->request->getPost('mrp'),
                    'dp'            => $this->request->getPost('dp'),
                    'bv_bonous'     => $this->request->getPost('bv_bonous'),
                    'package_image' => $newName,
                    'descriptions'      => htmlspecialchars($this->request->getPost('comments')),
                    'created_date'  => date('Y-m-d H:i:s')
                ];
                $statusId = $this->Product_model->insertPackageDetails($data);
                $inventory = [
                    'package_id' => $statusId,
                    'sku' => $packageCode,
                    'stocks' => $this->request->getPost('open_stock'),
                    'created_date'  => date('Y-m-d H:i:s')
                ];
                $this->Product_model->updatePackageInventory($inventory);
                $addProductList = $this->request->getPost('invoice');
                foreach ($addProductList as $key => $value) {
                    # code...
                    $productDetails = $this->Product_model->getProductDetails($value['product_id']);
                    $productInv = $this->Product_model->loadInventoryByProductId($value['product_id']);
                    $newProductData = [
                        'package_id' => $statusId,
                        'product_id' => $value['product_id'],
                        'product_name' => $productDetails[0]->product_name,
                        'mrp' => $value['mrp'],
                        'dp' => $value['dp'],
                        'bv_bonous' => $value['bv_bonous'],
                        'added_stocks' => $value['open_stock'],
                        'created_date'  => date('Y-m-d H:i:s')
                    ];
                    $insertStatus = $this->Product_model->insertPackageProducts($newProductData);
                    if ($insertStatus) {
                        # code...
                        $data = [
                            'product_id' => $value['product_id'],
                            'stocks' => (int)($productInv[0]->stocks) - ((int)$value['open_stock'] * (int)$this->request->getPost('open_stock')),
                        ];

                        $this->Product_model->updateInventory($data);
                        $history = [
                            'product_id'    => $value['product_id'],
                            'inventory_status' => '0',
                            'added_stocks'  => ((int)$value['open_stock'] * (int)$this->request->getPost('open_stock')),
                            'remove_reason' => 'Used in product package-list, Name: ' . $this->request->getPost('package_name'),
                            'created_date'  => date('Y-m-d H:i:s')
                        ];
                        $this->Product_model->insertInventoryHistory($history);
                    }
                }
                $this->setSessionNotification('wp_page', true, 'success', 'Package Inserted Successfully');
            } else {
                $this->setSessionNotification('wp_page', false, 'error', 'Package SKU already Exit.');
            }
            return $this->redirectToUrl('wp-dashboard/products/package-list');
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function changePackageStatus($id = '', $status = false)
    {
        if ($this->isAdmin() || $this->isSuperAdmin()) {
            if (!empty($this->Product_model->getPackageDetails($id))) {
                # code...
                $data = [
                    'status' => $status
                ];
                $this->Product_model->updatePackageStatus($id, $data);
                $this->setSessionNotification('wp_page', true, 'success', 'Package Status updated successfully.');
            } else {
                $this->setSessionNotification('wp_page', false, 'error', 'Invalid details given. please try again later.');
            }
            return $this->redirectToUrl('wp-dashboard/products/package-list');
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function editPackageList($id)
    {
        if ($this->isAdmin() || $this->isSuperAdmin()) {
            if (!empty($this->Product_model->getPackageDetails($id))) {
                $packgeList = $this->Product_model->getPackageDetails($id);
                $loadCategory = $this->Product_model->getAllCategory();
                if (!empty($loadCategory)) {
                    # code...
                    $loadCategory = array_map(function ($value) {
                        $value->subcategory_list = $this->Product_model->getSubcategoryList($value->id);
                        return $value;
                    }, $loadCategory);
                }
                if (!empty($packgeList)) {
                    # code...
                    $packgeList = array_map(function ($value) {
                        $value->category_name = $this->Product_model->getCategory($value->cat_id);
                        $value->current_stocks   = $this->Product_model->loadPackageInventory($value->id);
                        $value->added_product    = $this->Product_model->loadPackageProduct($value->id);
                        return $value;
                    }, $packgeList);
                }
                $data = [
                    'currentPage' => '/wp-dashboard/products/package-list',
                    'pageName' => 'Package List',
                    'category_list' => $loadCategory,
                    'package_list' => $packgeList[0]
                ];
                // echo "<pre>";
                // print_r($data);
                // exit;
                return $this->adminView('package-details', 'Products', $data);
            } else {
                $this->setSessionNotification('wp_page', false, 'error', 'Invalid details given. please try again later.');
                return $this->redirectToUrl('wp-dashboard/products/package-list');
            }
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function updatePackageImage()
    {
        if ($this->isAdmin() || $this->isSuperAdmin()) {
            $packageId = $this->request->getPost('package_id');
            if (!empty($this->Product_model->getPackageDetails($packageId))) {
                $productDetails = $this->Product_model->getPackageDetails($packageId);
                $frontFile = WRITEPATH . 'uploads/packages/' . $productDetails[0]->package_image;
                $uploadDir = WRITEPATH . 'uploads/packages';
                if (file_exists($frontFile)) {
                    # code...
                    unlink($frontFile);
                }
                $productImage = $this->request->getFile('update');
                $newName = '';
                if ($productImage->isValid() && !$productImage->hasMoved()) {
                    $newName = $productImage->getRandomName();
                    $productImage->move($uploadDir, $newName);
                }
                $data = [
                    'package_image' => $newName
                ];
                $this->Product_model->updatePackageStatus($packageId, $data);
                return $this->showAJAXReq(true, 'Data updated successfully', ['updated_path' => WEBSITE . '/writable/uploads/packages/' . $newName]);
            } else {
                $this->setSessionExpiredNotification();
                return $this->redirectToUrl('home');
            }
        }
    }

    public function updatePackageDetails()
    {
        if ($this->isAdmin() || $this->isSuperAdmin()) {

            $packgeId = $this->request->getPost('package_id');
            $isUpdateProduct = $this->request->getPost('is_update_product');
            if (!empty($this->Product_model->getPackageDetails($packgeId))) {
                $inventory = [
                    'package_id' => $packgeId,
                    'stocks' => $this->request->getPost('open_stock'),
                ];
                $data = [
                    'package_name' => $this->request->getPost('package_name'),
                    'mrp' => $this->request->getPost('mrp'),
                    'dp' => $this->request->getPost('dp'),
                    'bv_bonous' => $this->request->getPost('bv_bonous'),
                    'descriptions' => $this->request->getPost('comments'),
                    'updated_date' => date('Y-m-d')
                ];

                $this->Product_model->updatePackageStatus($packgeId, $data);
                if ($isUpdateProduct == 'on') {
                    $this->Product_model->updatePackageInventory($inventory);
                    $removeStatus = $this->Product_model->removePackageProduct($packgeId);
                    if ($removeStatus) {
                        # code...
                        $addProductList = $this->request->getPost('invoice');
                        foreach ($addProductList as $key => $value) {
                            # code...
                            $productDetails = $this->Product_model->getProductDetails($value['product_id']);
                            $productInv = $this->Product_model->loadInventoryByProductId($value['product_id']);
                            $newProductData = [
                                'package_id' => $packgeId,
                                'product_id' => $value['product_id'],
                                'product_name' => $productDetails[0]->product_name,
                                'mrp' => $value['mrp'],
                                'dp' => $value['dp'],
                                'bv_bonous' => $value['bv_bonous'],
                                'added_stocks' => $value['open_stock'],
                                'created_date'  => date('Y-m-d H:i:s')
                            ];
                            $insertStatus = $this->Product_model->insertPackageProducts($newProductData);
                            if ($insertStatus) {
                                # code...
                                $stock = (int)($productInv[0]->stocks) - ((int)$value['open_stock'] * (int)$this->request->getPost('open_stock'));
                                $data = [
                                    'product_id' => $value['product_id'],
                                    'stocks' => $stock > 0 ? $stock : 0,
                                ];

                                $this->Product_model->updateInventory($data);
                                $history = [
                                    'product_id'    => $value['product_id'],
                                    'inventory_status' => '0',
                                    'added_stocks'  => ((int)$value['open_stock'] * (int)$this->request->getPost('open_stock')),
                                    'remove_reason' => 'Updated in product package-list, Name: ' . $this->request->getPost('package_name'),
                                    'created_date'  => date('Y-m-d H:i:s')
                                ];
                                $this->Product_model->insertInventoryHistory($history);
                            }
                        }
                    }
                }
                $this->setSessionNotification('wp_page', true, 'success', 'Package Updated Successfully');
            } else {
                $this->setSessionNotification('wp_page', false, 'error', 'Invalid details given. please try again later.');
            }
            return $this->redirectToUrl('wp-dashboard/products/package-list');
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function productInvtoryHistory($pId = '')
    {
        if ($this->isAdmin() || $this->isSuperAdmin()) {
            # code...
            if (!empty($this->Product_model->getProductDetails($pId))) {
                $productDetails = $this->Product_model->getProductDetails($pId);
                $productCurrentInv = $this->Product_model->loadInventoryByProductId($pId);
                $productInvHistory = $this->Product_model->loadProductInvHistory($pId);
                $data = [
                    'currentPage'    => '/wp-dashboard/products/product-list',
                    'pageName'       => 'Product Inventory History',
                    'invHistory'     => $productInvHistory,
                    'productDetail'  => $productDetails[0],
                    'productCurrInv' => $productCurrentInv
                ];
                // echo "<pre>";
                // print_r($pId);exit;
                // echo "</pre>";
                return $this->adminView('product-inventory-history', 'Products', $data);
            } else {
                $this->setSessionNotification('wp_page', false, 'error', 'Invalid details given. please try again later.');
                return $this->redirectToUrl('/wp-dashboard/products/product-list');
            }
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function packageInvtoryHistory($pId = '')
    {
        if ($this->isAdmin() || $this->isSuperAdmin()) {
            # code...
            if (!empty($this->Product_model->getPackageDetails($pId))) {
                $productDetails = $this->Product_model->getPackageDetails($pId);
                $productCurrentInv = $this->Product_model->loadPackageInventory($pId);
                $productInvHistory = $this->Product_model->loadPackageInvHistory($pId);
                $data = [
                    'currentPage'    => '/wp-dashboard/products/package-list',
                    'pageName'       => 'Package Inventory History',
                    'invHistory'     => $productInvHistory,
                    'productDetail'  => $productDetails[0],
                    'productCurrInv' => $productCurrentInv
                ];
                return $this->adminView('package-inventory-history', 'Products', $data);
            } else {
                $this->setSessionNotification('wp_page', false, 'error', 'Invalid details given. please try again later.');
                return $this->redirectToUrl('/wp-dashboard/products/package-list');
            }
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }
    /*************************************************************** ORDER MODUL ***************************************************************/

    public function loadOrderList()
    {
        if ($this->checkSessionStatus()) {
            # code...
            $orderList = [];
            if ($this->isMember()) {
                # code...
                $orderList = $this->Product_model->getOrderList('', $this->session->get('user_id'));
                if (!empty($orderList)) {
                    # code...
                    foreach ($orderList as $key => $value) {
                        # code...
                        
                        $value->totalQty = 0;
                        $packageList = $this->Product_model->getOrderPackagesInv($value->id);
                        $productList = $this->Product_model->getOrderProductsInv($value->id);
                        if (!empty($productList)) {
                            # code...
                            foreach ($productList as $Pkey => $Pvalue) {
                                # code...
                                $value->totalQty += (int)($Pvalue->qty);
                            }
                        }

                        if (!empty($packageList)) {
                            # code...
                            foreach ($packageList as $cKey => $Cvalue) {
                                # code...
                                $value->totalQty += (int)($Cvalue->qty);
                            }
                        }
                    }
                }
            } else {
                $orderList = $this->Product_model->getOrderList($this->session->get('user_id'));
                if (!empty($orderList)) {
                    # code...
                    foreach ($orderList as $key => $value) {
                        # code...
                        $value->totalQty = 0;
                        $packageList = $this->Product_model->getOrderPackagesInv($value->id);
                        $productList = $this->Product_model->getOrderProductsInv($value->id);
                        if (!empty($productList)) {
                            # code...
                            foreach ($productList as $Pkey => $Pvalue) {
                                # code...
                                $value->totalQty += (int)($Pvalue->qty);
                            }
                        }

                        if (!empty($packageList)) {
                            # code...
                            foreach ($packageList as $cKey => $Cvalue) {
                                # code...
                                $value->totalQty += (int)($Cvalue->qty);
                            }
                        }
                    }
                }
            }
            $data = [
                'currentPage'    => '/wp-dashboard/products/order-list',
                'pageName'       => 'Order List',
                'orderList'      => $orderList
            ];
            return $this->adminView('Order-list', 'Products/Orders', $data);
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function createNewOrder()
    {
        if ($this->isMember()) {
            # code...
            $filterFranchiseList = [];
            $productList = [];
            $packageList = [];
            if ($this->request->getGet('stateId')) {
                # code...
                $filterFranchiseList = $this->Common_model->fetchFranchiseByStateId($this->request->getGet('stateId'));
            }

            if ($this->request->getGet('franchise_id')) {
                # code...   
                $franchiseId = $this->request->getGet('franchise_id');
                $productList = $this->Product_model->getFranchiseAccessProducts($franchiseId);
                $packageList = $this->Product_model->getFranchiseAccessPackages($franchiseId);
            }
                
            $data = [
                'currentPage'    => '/wp-dashboard/products/order-list',
                'pageName'       => 'Create new order',
                'stateList'      => $this->Common_model->getAllStateList()->getResult(),
                'productList'    => $productList,
                'packageList'    => $packageList,
                'franchise_list' =>  $filterFranchiseList
            ];

            return $this->adminView('new-order', 'Products/Orders', $data);
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function createOrder()
    {
        
        if ($this->checkSessionStatus()) {
            # code...
            $orderId = $this->getAutoGeneratedOrderId();
            $totalBV = $totalDP = $totalMRP = 0;
            $productList = $this->request->getPost('product');
            $packageList = $this->request->getPost('package');
            $franchiseId = $this->request->getPost('select_franchise');
            $orderData =  [
                'order_id' => $orderId,
                'mem_id'   => $this->session->get('user_id'),
                'franchise_id' => $this->request->getPost('select_franchise'),
                'state_id'  => $this->request->getPost('state_name'),
                'created_date'  => date('Y-m-d H:i:s')
            ];
            $lastOrderId = $this->Product_model->insertUserOrders($orderData);
            if ($lastOrderId) {
                # code...
                if (!empty($productList)) {
                    # code...
                    foreach ($productList as $key => $value) {
                        # code...
                        $productInv = $this->Product_model->getFranchiseInvDetails($franchiseId, $value['product_id'], '1');
                        $productList = $this->Product_model->getProductDetails($value['product_id']);
                        if (!empty($productInv)) {
                            # code...
                            $productData = [
                                'order_id'   => $lastOrderId,
                                'product_id' => $value['product_id'],
                                'qty'        => $value['qty'],
                                'bv_amout'   => $productList[0]->bv_bonous,
                                'dp_amount'  => $productList[0]->dp,
                                'mrp_amount' => $productList[0]->mrp
                            ];
                            $totalBV  = $totalBV + ((float)$productList[0]->bv_bonous * (int)$value['qty']);
                            $totalDP  = $totalDP + ((float)$productList[0]->dp * (int)$value['qty']);
                            $totalMRP = $totalMRP + ((float)$productList[0]->mrp * (int)$value['qty']);
                            $this->Product_model->insertOrderProducts($productData);
                        }
                    }
                }
                if (!empty($packageList)) {
                    # code...
                    foreach ($packageList as $key => $value) {
                        # code...
                        $packageInv  = $this->Product_model->getFranchiseInvDetails($franchiseId, $value['package_id'], '2');
                        $packageList = $this->Product_model->getPackageDetails($value['package_id']);
                        if (!empty($packageInv)) {
                            # code...
                            $packageData = [
                                'order_id'      => $lastOrderId,
                                'package_id'    => $value['package_id'],
                                'qty'           => $value['qty'],
                                'bv_amout'      => $packageList[0]->bv_bonous,
                                'dp_amount'     => $packageList[0]->dp,
                                'mrp_amount'    => $packageList[0]->mrp
                            ];
                            $totalBV  = $totalBV + ((float)$packageList[0]->bv_bonous * (int)$value['qty']);
                            $totalDP  = $totalDP + ((float)$packageList[0]->dp * (int)$value['qty']);
                            $totalMRP = $totalMRP + ((float)$packageList[0]->mrp * (int)$value['qty']);
                            $this->Product_model->insertOrderPackages($packageData);
                        }
                    }
                }
                $updateOrder = [
                    'total_bv' => $totalBV,
                    'total_dp' => $totalDP,
                    'total_mrp' => $totalMRP
                ];


                $pStatus = $this->Product_model->updateCustomerOrder($updateOrder, $lastOrderId);
                if ($pStatus) {
                    # code...
                    $getFranchiseDetails = $this->Common_model->getFranchiseDetailsId($this->request->getPost('select_franchise'));
                    $mailData = [
                        'from_name' => 'Deltavo',
                        'to_email'  =>  $getFranchiseDetails[0]->email,
                        'subject'   =>  'New order placed with the order id -' . $orderId,
                        'mailType'  =>  'product_order',
                        'userArray' =>  [
                            'user_name' =>  $getFranchiseDetails[0]->user_name,
                            'mobile_header' => 'A new order has been placed.',
                            'message'   => "A order has been placed by the user " . $this->session->get('user_name') . ". The current status is pending and automatically deactivated on last of " . date("d-m-Y") . " this month . If not activated with in the time."
                        ]
                    ];
                    $notificationData = [
                        'user_id'  => $this->request->getPost('select_franchise'),
                        'message'  => 'A new order id .' . $orderId . ' has been placed by ' . $this->session->get('user_name'),
                        'read_status' => '1',
                        'redirect_url' => '/wp-dashboard/invoice/orders-list/' . $orderId
                    ];
                    $this->Common_model->pushAppNotifications($notificationData);
                    $this->sendEmail($mailData, 2);
                    $this->setSessionNotification('wp_page', true, 'success', 'Your order generated successfully and  the details sent to the Email id.');
                } else {
                    $this->setSessionNotification('wp_page', false, 'error', 'Not able to insert the order. Please try again later.');
                }
            } else {
                $this->setSessionNotification('wp_page', false, 'error', 'Invalid details given. please try again later.');
            }
            return $this->redirectToUrl('wp-dashboard/products/orders-list');
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function orderDetails($orderId = '')
    {
        if ($this->checkSessionStatus()) {
            # code...
            $orderDetails = $this->Product_model->getOrderByOrderId($orderId);
            if (!empty($orderDetails)) {
                # code...
                $productList = $this->Product_model->getOrderProductsRelational($orderDetails[0]->id);
                $packageList = $this->Product_model->getOrderPackagesRelational($orderDetails[0]->id);
                $franchiseDetails =  $this->Common_model->getFranchiseDetailsId($orderDetails[0]->franchise_id);
                $memberDetails = $this->Common_model->getMemberDetailsId($orderDetails[0]->mem_id);
                $data = [
                    'currentPage'    => '/wp-dashboard/products/order-list',
                    'pageName'       => 'Order List',
                    'productList' => $productList,
                    'packageList' => $packageList,
                    'franchiseDetails' => $franchiseDetails,
                    'orderDetails' => $orderDetails,
                    'memberDetails' => $memberDetails
                ];

                // echo "<pre>";
                // print_r ($data);
                // echo "</pre>";
                // exit;
                return $this->adminView('order-details', 'Products/Orders', $data);
            } else {
                $this->setSessionNotification('wp_page', false, 'error', 'Invalid details given. please try again later.');
                return $this->redirectToUrl('wp-dashboard/products/orders-list');
            }
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }
    /**
     * Used for filtering and returning all filtered UPLINE members.
     * @return boolean of the upline member class
     */
    function getAllUperLineMem($myUplines, $getAllMembers)
    {
        foreach ($myUplines as $key => $value) {
            # code...
            foreach ($getAllMembers as $ckey => $cvalue) {
                # code...
                // echo $key . 'PKey <br>';
                // echo strtolower(str_replace(' ', '', $value->tracking_id)) . " == " .strtolower(str_replace(' ', '', $cvalue->members_id)). '<br>';
                if (strtolower(str_replace(' ', '', $value->tracking_id)) == strtolower(str_replace(' ', '', $cvalue->members_id))) {
                    //     # code...
                    // echo $ckey . 'CKey <br>';
                    array_push($myUplines, $cvalue);
                    array_splice($getAllMembers, $ckey, 1);
                    $this->getAllUperLineMem($myUplines, $getAllMembers);
                    break;
                }
            }
        }
        if (count($myUplines) > count($this->globalUplineMember)) {
            # code...
            $this->globalUplineMember = $myUplines;
            return;
        }
    }
    public function buyMemStockOrders()
    {
        if ($this->isFranchise()) {
            # code...
            $this->globalUplineMember = [];
            $franchiseAccount = $this->Common_model->getFranchiseKYCStatus($this->getSessions('user_id'));
            if (empty($franchiseAccount)) {
                $this->setSessionNotification('wp_page', false, 'error', 'You need to add your bank account to continue buying the order.');
                return $this->redirectToUrl('wp-dashboard/invoice/orders-list');
            } elseif (!$franchiseAccount[0]->gst_no) {
                $this->setSessionNotification('wp_page', false, 'error', 'You need to set the GST number');
                return $this->redirectToUrl('wp-dashboard/invoice/orders-list');
            }
            $orderId = $this->request->getPost('order_id');
            $franchiseData = [];
            $notificationData = [];
            $orderDetails = $this->Product_model->getOrderByOrderId($orderId);
            # Getting Member Details selected people
            $memberDetails = $this->Common_model->getCustomMemSelectDataByUserId($orderDetails[0]->mem_id, 'current_level,full_name,total_bv,tracking_id,milstone_bv, tracking_id, members_id, accumulated_bv, promotion_date, login_access, user_id')[0];
            if ($memberDetails->login_access == '0') {
                $this->setSessionNotification('wp_page', false, 'error', 'This Member Accound is Deactivated. You can not Buy the Product for this account.');
                return $this->redirectToUrl('wp-dashboard/invoice/orders-list');
            }
            # Getting all the past promoted position for the requested member
            $promotionDataByUserId = $this->Common_model->getPromotionalCustomer($orderDetails[0]->mem_id);
            $this->generateInvoiceOrderUserId($orderDetails[0]);
            # Getting all memebers details
            $getAllMembers = $this->Common_model->getAllFilerMembers($orderDetails[0]->mem_id);
            if (!empty($orderDetails)) {
                $transId =  $this->getMemberTransationID();
                // $this->setPerformanceBonousIncome($memberDetails, $orderDetails[0]);
                $totalOrderBVThisMonth =  $this->Common_model->getMemberCurrentMonthBV($orderDetails[0]->mem_id)[0]->total_bv ? $this->Common_model->getMemberCurrentMonthBV($orderDetails[0]->mem_id)[0]->total_bv : 0;
                $updateTranstation = [
                    'transation_id' => $transId,
                    'transport_by'  => $this->request->getPost('transport_by'),
                    'payment_mode'  => $this->request->getPost('payment_mode'),
                    'remarks'       => $this->request->getPost('remarks'),
                    'order_status'  => '2',
                    'updated_date'  => date('Y-m-d H:i:s')
                ];
                # code...

                $getOrderProductDetails = $this->Product_model->getOrderProducts($orderDetails[0]->id);
                $getOrderPackageDetails = $this->Product_model->getOrderPackages($orderDetails[0]->id);
                # code...
                $filterOrderProduct = array_values(array_filter($getOrderProductDetails, function ($value) use ($orderDetails) {
                    $qty = 0;
                    $invStocks = $this->Product_model->getFranchiseInvDetails($orderDetails[0]->franchise_id, $value->product_id, '1');
                    if (!empty($invStocks)) {
                        $qty = (int)$invStocks[0]->total_stocks  - (int)$value->qty;
                        if ($qty < 0) {
                            return $value;
                        }
                    } else {
                        return $value;
                    }
                }));

                $filterOrderPackage = array_values(array_filter($getOrderPackageDetails, function ($value) use ($orderDetails) {
                    $qty = 0;
                    $invStocks = $this->Product_model->getFranchiseInvDetails($orderDetails[0]->franchise_id, $value->package_id, '2');
                    if (!empty($invStocks)) {
                        $qty = (int)$invStocks[0]->total_stocks  - (int)$value->qty;
                        if ($qty < 0) {
                            return $value;
                        }
                    } else {
                        return $value;
                    }
                }));

                if (!empty($getOrderProductDetails)) {
                    if (!empty($filterOrderProduct)) {
                        # code...
                        $this->setSessionNotification('wp_page', false, 'error', 'Few of your ordered product has been out of stocks. Please make the item to instocks continuing the order.');
                        return $this->redirectToUrl('wp-dashboard/products/orders-list');
                    }
                    foreach ($getOrderProductDetails as $key => $value) {
                        # code...
                        $qty = 0;
                        $invStocks = $this->Product_model->getFranchiseInvDetails($orderDetails[0]->franchise_id, $value->product_id, '1');
                        if (!empty($invStocks)) {
                            # code...
                            $qty = (int)$invStocks[0]->total_stocks  - (int)$value->qty;
                            if ($qty < 0) {
                                # code...
                                $this->setSessionNotification('wp_page', false, 'error', 'Few of your ordered product has been out of stocks. Please make the item to instocks continuing the order.');
                                return $this->redirectToUrl('wp-dashboard/products/orders-list');
                            }
                            $updateInvData = [
                                'total_stocks'   => $qty,
                                'updated_date'   => date('Y-m-d')
                            ];
                            $status = $this->Product_model->updateFranchiseInvDetailsByMem($updateInvData, $value->product_id, $orderDetails[0]->franchise_id, '1');
                            if ($status) {
                                # code...
                                $insertFranchiseInvHis = [
                                    'franchise_id' => $orderDetails[0]->franchise_id,
                                    'product_id'   => $value->product_id,
                                    'stock_type'   => '0',
                                    'stocks'        => $value->qty,
                                    'comments'     => $orderId.' has been placed by.',
                                    'created_date'  => date('Y-m-d H:i:s')
                                ];
                                $this->Product_model->insertFranchiseStockDetails($insertFranchiseInvHis, '1');
                            }
                        } else {
                            $this->setSessionNotification('wp_page', false, 'error', 'Few of your ordered product has been out of stocks. Please make the item to instocks continuing the order.');
                            return $this->redirectToUrl('wp-dashboard/products/orders-list');
                        }
                    }
                }

                if (!empty($getOrderPackageDetails)) {
                    # code...
                    if (!empty($filterOrderPackage)) {
                        # code...
                        $this->setSessionNotification('wp_page', false, 'error', 'Few of your ordered package has been out of stocks. Please make the item to instocks continuing the order.');
                        return $this->redirectToUrl('wp-dashboard/products/orders-list');
                    }
                    foreach ($getOrderPackageDetails as $key => $value) {
                        # code...
                        $qty = 0;
                        $invStocks = $this->Product_model->getFranchiseInvDetails($orderDetails[0]->franchise_id, $value->package_id, '2');
                        if (!empty($invStocks)) {
                            # code...
                            $qty = (int)$invStocks[0]->total_stocks  - (int)$value->qty;
                            if ($qty < 0) {
                                # code...
                                $this->setSessionNotification('wp_page', false, 'error', 'Few of your ordered package has been out of stocks. Please make the item to instocks continuing the order.');
                                return $this->redirectToUrl('wp-dashboard/products/orders-list');
                            }
                            $updateInvData = [
                                'total_stocks'   => $qty,
                                'updated_date'   => date('Y-m-d')
                            ];
                            $status = $this->Product_model->updateFranchiseInvDetailsByMem($updateInvData, $value->package_id, $orderDetails[0]->franchise_id, '2');
                            if ($status) {
                                # code...
                                $insertFranchiseInvHis = [
                                    'franchise_id' => $orderDetails[0]->franchise_id,
                                    'package_id'   => $value->package_id,
                                    'stock_type'   => '0',
                                    'stocks'        => $value->qty,
                                    'comments'     => $orderId.' has been placed by.',
                                    'created_date'  => date('Y-m-d H:i:s')
                                ];
                                $this->Product_model->insertFranchiseStockDetails($insertFranchiseInvHis, '2');
                            }
                        } else {
                            $this->setSessionNotification('wp_page', false, 'error', 'Few of your ordered product has been out of stocks. Please make the item to instocks continuing the order.');
                            return $this->redirectToUrl('wp-dashboard/products/orders-list');
                        }
                    }
                }
                $this->Product_model->updateCustomerOrder($updateTranstation, $orderDetails[0]->id);
                $notificationData = [
                    'user_id'  => $orderDetails[0]->mem_id,
                    'message'  => "One of your order $orderId has been accepted and a Invoice on the order successfully generated.",
                    'read_status' => '1',
                    'redirect_url' => '/wp-dashboard/invoice/self-invoice'
                ];
                $getFranchiseDetails = $this->Common_model->getFranchiseDetailsId($this->session->get('user_id'));
                $franchiseData = [
                    'available_credits' => $getFranchiseDetails[0]->available_credits - (float)($orderDetails[0]->total_dp)
                ];
                $this->setPerformanceBonousIncome($memberDetails, $orderDetails[0]);
                // Logice for promotional behaviour

                $myUplines = [];
                $myDownlineTotal = $this->loadMyDownLinesTotal($memberDetails->members_id, $memberDetails->tracking_id);
                foreach ($getAllMembers as $key => $value) {
                    # code...
                    if ($value->members_id === $memberDetails->tracking_id) {
                        # code...
                        array_push($myUplines, $value);
                        array_splice($getAllMembers, $key, 1);
                    }
                }
                if (!empty($myUplines)) {
                    # code...
                    $this->getAllUperLineMem($myUplines, $getAllMembers); // Getting my upline members 
                }

                // $orderDetails[0]->total_bv = 80;
                // echo 'This month BV => ' . $totalOrderBVThisMonth . "<br>";
                // $promotionDataByUserId = [];
                // $myDownlineTotal = 0;
                // $memberDetails->current_level = '2';
                // $memberDetails->total_bv = 70;
                $currentLevel = $memberDetails->current_level;
                $mileStone = (float)$memberDetails->milstone_bv;
                $extraBonous = $this->Common_model->getExtraBonous($orderDetails[0]->mem_id);
                $extraBonousAmount = !empty($extraBonous) ? $extraBonous[0]->added_bv : 0.00;
                $totalBVAmount = $bvAmount = (float)$memberDetails->total_bv +  (float)$orderDetails[0]->total_bv + $myDownlineTotal + $extraBonousAmount;
                $totalDownlineAmount = (float)$orderDetails[0]->total_bv + $myDownlineTotal + $totalOrderBVThisMonth + $extraBonousAmount;
                $mileStoneAmount = (float)$memberDetails->milstone_bv;
                $currentDate = date('Y-m-d');
                $accumulatedBV = $memberDetails->accumulated_bv ? $memberDetails->accumulated_bv : 0;
                if ($currentDate > $memberDetails->promotion_date) {
                    // echo $currentDate . ">" . $memberDetails->promotion_date;
                    $accumulatedBV = ($accumulatedBV + $orderDetails[0]->total_bv);
                }
                $d = new DateTime(date('Y-m-d'));
                $d->modify('first day of next month');
                if (empty($promotionDataByUserId)) {
                    # code... This mean the member have not purchase any product amout before
                    if (((float)$memberDetails->total_bv <= 0 && $memberDetails->current_level == '1' && (float)$orderDetails[0]->total_bv >= 25) || ($memberDetails->current_level == '1')) {
                        # code... Logic for the Business Leader Promotion
                        // echo $orderDetails[0]->total_bv . '1=>' . $totalDownlineAmount;
                        echo "here";exit;
                        $currentLevel = '2';
                        $promotionData = [
                            'user_id' => $orderDetails[0]->mem_id,
                            'transation_id'   => $transId,
                            'current_level'   => $currentLevel,
                            'added_bv' => $orderDetails[0]->total_bv,
                            'created_date' => date('Y-m-d H:i:s'),
                        ];
                        $promotionHistory = [
                            'user_id' => $orderDetails[0]->mem_id,
                            'promtion_type' => '0',
                            'promotion_label' =>   $currentLevel,
                            'created_date' => date('Y-m-d H:i:s')
                        ];
                        $updateUserData = [
                                    'current_level' => '2',
                                    'promotion_date' => date('Y-m-d'),
                                    'updated_date'   => date('Y-m-d'),
                        ];
                        $this->Common_model->updateMemberAccess(false, $value->user_id, $updateUserData);
                        $this->Common_model->insertPromotionHistory($promotionHistory);
                        $this->Product_model->insertPromotionRecords($promotionData);
                        foreach ($this->globalUplineMember as $key => $value) {
                            # code...
                            
                            if ($value->current_level == '1') {
                                # code...
                                
                                $updatemultiUserData = [
                                    'current_level' => '2',
                                    'promotion_date' => date('Y-m-d'),
                                    'updated_date'   => date('Y-m-d'),
                                ];
                                $this->Common_model->updateMemberAccess(false, $value->user_id, $updatemultiUserData);
                                $promotionHistory = [
                                    'user_id' => $value->user_id,
                                    'promtion_type' => '1',
                                    'promotion_label' =>   $currentLevel,
                                    'created_date' => date('Y-m-d H:i:s')
                                ];
                                $this->Common_model->insertPromotionHistory($promotionHistory);
                            }
                        }
                        $mileStone = 25;
                        
                        // code condition cheack from here
                    } 
                    
                } 
                else {
                    
                        # code... Logic for the Business Leader Promotion
                        // echo $orderDetails[0]->total_bv . '1=>' . $totalDownlineAmount;
                        echo "else";exit;
                        $currentLevel = '2';
                        $promotionData = [
                            'user_id' => $orderDetails[0]->mem_id,
                            'transation_id'   => $transId,
                            'current_level'   => $currentLevel,
                            'added_bv' => $orderDetails[0]->total_bv,
                            'created_date' => date('Y-m-d H:i:s'),
                        ];
                        $promotionHistory = [
                            'user_id' => $orderDetails[0]->mem_id,
                            'promtion_type' => '0',
                            'promotion_label' =>   $currentLevel,
                            'created_date' => date('Y-m-d H:i:s')
                        ];
                        $updateUserData = [
                                    'current_level' => '2',
                                    'promotion_date' => date('Y-m-d'),
                                    'updated_date'   => date('Y-m-d'),
                        ];
                        $this->Common_model->updateMemberAccess(false, $value->user_id, $updateUserData);
                        $this->Common_model->insertPromotionHistory($promotionHistory);
                        $this->Product_model->insertPromotionRecords($promotionData);
                        foreach ($this->globalUplineMember as $key => $value) {
                            # code...
                            
                            if ($value->current_level == '1') {
                                # code...
                                
                                $updatemultiUserData = [
                                    'current_level' => '2',
                                    'promotion_date' => date('Y-m-d'),
                                    'updated_date'   => date('Y-m-d'),
                                ];
                                $this->Common_model->updateMemberAccess(false, $value->user_id, $updatemultiUserData);
                                $promotionHistory = [
                                    'user_id' => $value->user_id,
                                    'promtion_type' => '1',
                                    'promotion_label' =>   $currentLevel,
                                    'created_date' => date('Y-m-d H:i:s')
                                ];
                                $this->Common_model->insertPromotionHistory($promotionHistory);
                            }
                        }
                        $mileStone = 25;
                        
                        // code condition cheack from here
                    
                    
                }
                $newLevel = '1';
                if ($currentLevel == '2') $newLevel = $currentLevel;
                elseif ($totalBVAmount >= 25 && $memberDetails->current_level == '1' && $currentLevel != '2') $newLevel = '2';
                else $newLevel = $memberDetails->current_level;
                if (!empty($this->Common_model->getCurrentMonthPromotionCount($orderDetails[0]->mem_id))) {
                    $accumulatedBV = 0;
                }
                $updateUserTerminal = [
                    'total_bv'  => $totalBVAmount - $myDownlineTotal,
                    'milstone_bv' => $mileStone,
                    'current_level'   => $newLevel,
                    'upgrade_level' => $currentLevel,
                    'is_direct_upgrade' => '1',
                    'updated_date'   => date('Y-m-d'),
                    'accumulated_bv' => $accumulatedBV,
                    'promotion_date' => $currentLevel <= $memberDetails->current_level ? null : $d->format('Y-m-d')
                ];

                // echo "<pre>";
                // print_r($updateUserTerminal);
                // print_r($orderDetails);
                // echo "</pre>";
                // exit;
                $this->Common_model->updateMemberAccess(false, $orderDetails[0]->mem_id, $updateUserTerminal);
                $memberDetails->total_bv = $totalBVAmount - $myDownlineTotal;
                if ($currentLevel == '2') {
                    $this->generatePerformanceIncomeRecordsBySponserClub($memberDetails, $orderDetails, $currentLevel, $totalOrderBVThisMonth);
                } elseif ($memberDetails->current_level == '2' || $memberDetails->current_level == '3') {
                    # code...
                    $this->generatePerformanceIncomeRecordsBySponserClub($memberDetails, $orderDetails, $memberDetails->current_level, $totalOrderBVThisMonth);
                }

                if ($memberDetails->current_level > 3) {
                    # code...
                    $this->generatedPerforManceIncomeRecordsByLeaderShipClub($memberDetails, (float)$orderDetails[0]->total_bv);
                    $this->generatedPerforManceIncomeRecordsByStarShipClub($memberDetails, (float)$orderDetails[0]->total_bv);
                    $this->generatedPerforManceIncomeRecordsByDyanmicClub($memberDetails, (float)$orderDetails[0]->total_bv);
                    $this->generateIncomeRecordsBySpecialClub($memberDetails, $orderDetails[0]);
                    $this->generateIncomeRecordsByTargetClub($memberDetails, $orderDetails[0]);
                }
                // Changed Method will be here
                // $this->Common_model->updateFranchiseAcess(false, $this->session->get('user_id'), $franchiseData);
                // $this->Common_model->pushAppNotifications($notificationData);
                // $this->setSessionNotification('wp_page', true, 'success', 'You have successfully bought the order and a invoice with the order id generated.');
                // return $this->redirectToUrl('wp-dashboard/invoice/reports');
            } else {
                $this->setSessionNotification('wp_page', false, 'error', 'Invalid details given. please try again later.');
                return $this->redirectToUrl('wp-dashboard/invoice/reports');
            }
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }
    public function buyOrder($orderId = '')
    {
        if ($this->isSuperFrnachise() || $this->isFranchise()) {
            # code...
            $orderDetails = $this->Product_model->getOrderByOrderId($orderId);
            if (!empty($orderDetails)) {
                # code...
                $productList = $this->Product_model->getOrderProductsRelational($orderDetails[0]->id);
                $packageList = $this->Product_model->getOrderPackagesRelational($orderDetails[0]->id);
                $franchiseDetails =  $this->Common_model->getFranchiseDetailsId($orderDetails[0]->franchise_id);
                $memberDetails = $this->Common_model->getMemberDetailsId($orderDetails[0]->mem_id);
                $data = [
                    'currentPage'    => '/wp-dashboard/invoice/orders-list',
                    'pageName'       => 'Buy order',
                    'productList' => $productList,
                    'packageList' => $packageList,
                    'franchiseDetails' => $franchiseDetails,
                    'orderDetails' => $orderDetails,
                    'memberDetails' => $memberDetails
                ];

                // echo "<pre>";
                // print_r ($data);
                // echo "</pre>";
                // exit;
                return $this->adminView('buy-order', 'Products/Orders', $data);
            } else {
                $this->setSessionNotification('wp_page', false, 'error', 'Invalid details given. please try again later.');
                return $this->redirectToUrl('wp-dashboard/invoice/orders-list');
            }
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    /**************************************************** CREATE REQUEST ************************************************/
    public function createRequest()
    {
        if ($this->isFranchise() || $this->isSuperFrnachise()) {
            # code...
            $loadFranchsie = $this->request->getGet('load_franchise');
            $productList = [];
            $packageList = [];
            if ($loadFranchsie) {
                # code...
                $productList = $this->Product_model->getFranchiseAccessProducts($loadFranchsie);
                $packageList = $this->Product_model->getFranchiseAccessPackages($loadFranchsie);
            } else {
                
                $productList = $this->Product_model->getAllProductList(true);
                $packageList = $this->Product_model->getAllPackageList(true);
            }
            $data = [
                'currentPage'    => '/wp-dashboard/stocks/create-request',
                'pageName'       => 'Create Request',
                'productList' => $productList,
                'packageList' => $packageList
            ];
            return $this->adminView('create-request', 'Products/Stocks', $data);
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function saveCreatedRequest()
    {
      
        if ($this->isFranchise() || $this->isSuperFrnachise()) {

            // echo "<pre>";
            // print_r ($this->request->getPost());
            // print_r($_FILES);
            // echo "</pre>";
            $transationId = $this->getAutoGeneratedTransationId();
            $totalBV = $totalDP = $totalMRP = $totalQty = 0;
            $productList = $this->request->getPost('product');
            $packageList = $this->request->getPost('package');
            $newName = '';
            $uploadDir = WRITEPATH . 'uploads/stocks';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, TRUE);
            }
            $reciptImg = $this->request->getFile('upload_file');
            $newName = '';
            if ($reciptImg->isValid() && !$reciptImg->hasMoved()) {
                $newName = $reciptImg->getRandomName();
                $reciptImg->move($uploadDir, $newName);
            }
            $transationData =  [
                'franchise_id' => $this->session->get('user_id'),
                'transation_id'   => $transationId,
                'payment_mode' => $this->request->getPost('payment_mode'),
                'transportation'  => $this->request->getPost('transport_by'),
                'remarks'  => $this->request->getPost('remarks'),
                'payment_receipt'  => $newName,
                'updated_userid' => $this->request->getPost('req_person_id'),
                'req_type' => $this->request->getPost('req_section'),
                'created_date'  => date('Y-m-d H:i:s')
            ];
            $lastTransatId = $this->Product_model->insertTransactionDetails($transationData);
            if ($lastTransatId) {
                # code...
                if (!empty($productList)) {
                    # code...
                    print_r($productList);
                    foreach ($productList as $key => $value) {
                        # code...
                        $productList = $this->Product_model->getProductDetails($value['product_id']);
                        if (!empty($productList)) {
                            # code...
                            $productData = [
                                'transation_id'   => $lastTransatId,
                                'product_id' => $value['product_id'],
                                'qty'        => $value['qty'],
                                'total_qty'  => $value['qty'],
                                'bv_amout'   => $productList[0]->bv_bonous,
                                'dp_amount'  => $productList[0]->dp,
                                'mrp_amount' => $productList[0]->mrp
                            ];
                            $totalBV  = $totalBV + ((float)$productList[0]->bv_bonous * (int)$value['qty']);
                            $totalDP  = $totalDP + ((float)$productList[0]->dp * (int)$value['qty']);
                            $totalMRP = $totalMRP + ((float)$productList[0]->mrp * (int)$value['qty']);
                            $totalQty  = $totalQty + (int)$value['qty'];
                            $this->Product_model->inserTransactionProduct($productData);
                        }
                    }
                }
                if (!empty($packageList)) {
                    # code...
                    foreach ($packageList as $key => $value) {
                        # code...
                        $packageList = $this->Product_model->getPackageDetails($value['package_id']);
                        if (!empty($packageList)) {
                            # code...
                            $packageData = [
                                'transation_id'      => $lastTransatId,
                                'package_id'    => $value['package_id'],
                                'qty'           => $value['qty'],
                                'total_qty'           => $value['qty'],
                                'bv_amout'      => $packageList[0]->bv_bonous,
                                'dp_amount'     => $packageList[0]->dp,
                                'mrp_amount'    => $packageList[0]->mrp
                            ];
                            $totalBV  = $totalBV + ((float)$packageList[0]->bv_bonous * (int)$value['qty']);
                            $totalDP  = $totalDP + ((float)$packageList[0]->dp * (int)$value['qty']);
                            $totalMRP = $totalMRP + ((float)$packageList[0]->mrp * (int)$value['qty']);
                            $totalQty  = $totalQty + (int)$value['qty'];
                            $this->Product_model->inserTransactionPackages($packageData);
                        }
                    }
                }
                $transationReq = [
                    'total_bv' => $totalBV,
                    'total_dp' => $totalDP,
                    'total_mrp' => $totalMRP,
                    'total_qty' => $totalQty
                ];
                $pStatus = $this->Product_model->updateFranchiseReq($transationReq, $lastTransatId);
                if ($pStatus) {
                    $this->setSessionNotification('wp_page', true, 'success', 'Your stock has been placed by successfully.');
                    return $this->redirectToUrl('/wp-dashboard/stocks/pending-request');
                } else {
                    $this->setSessionNotification('wp_page', false, 'error', 'Not able to insert the stocks. Please try again later.');
                    return $this->redirectToUrl('/wp-dashboard/stocks/create-request');
                }
            } else {
                $this->setSessionNotification('wp_page', false, 'error', 'Invalid details given. please try again later.');
                return $this->redirectToUrl('/wp-dashboard/stocks/create-request');
            }
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function pendingRequest()
    {
        if ($this->isFranchise() || $this->isSuperFrnachise()) {
            $data = [
                'currentPage'       => '/wp-dashboard/stocks/pending-list',
                'pageName'          => 'Pending List',
                'pending_request'   => $this->Product_model->getFilterReportByTranStatus($this->session->get('user_id'), '1')
            ];
            return $this->adminView('pending-list', 'Products/Stocks', $data);
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function acceptedRequest()
    {
        if ($this->isFranchise() || $this->isSuperFrnachise()) {
            $order_details = $this->Product_model->getFilterReportByTranStatus($this->session->get('user_id'), '23');
            foreach($order_details as $key=>$orderValue){
                $orderValue->invoiceID = (!empty($this->Product_model->getInvoiceBytransactionId($orderValue->id)))?$this->Product_model->getInvoiceBytransactionId($orderValue->id)[0]->invoice_id:"Not found";
                $orderProduct = $this->Product_model->gettotalQTYBytransactionId($orderValue->id,'1');
                $orderPackage = $this->Product_model->gettotalQTYBytransactionId($orderValue->id,'2');
                if(!empty($orderProduct)){
                    $orderValue->product = $orderProduct;
                    
                }
                if(!empty($orderPackage)){
                    $orderValue->product = $orderPackage;
                    
                }
                
            }
            $data = [
                'currentPage'       => '/wp-dashboard/stocks/accepted-list',
                'pageName'          => 'Accepted List',
                'accepted_reports'   => $order_details
            ];
            return $this->adminView('accepted-list', 'Products/Stocks', $data);
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function stockRequestDetails($transationId = '', $requType = '2')
    {
        $stockDetails = $this->Product_model->getFranchiseRequest($transationId, $requType);
        
        if (empty($stockDetails)) {
            if (($this->isFranchise() || $this->isSuperFrnachise())) {
                $this->setSessionNotification('wp_page', false, 'error', 'Invalid details given. please try again later.');
                return $this->redirectToUrl('/wp-dashboard/stocks/stock-reports');
            } else {
                $this->setSessionNotification('wp_page', false, 'error', 'Invalid details given. please try again later.');
                return $this->redirectToUrl('/wp-dashboard/franchise-settings/stocks/pending-request');
            }
        }
        if (($this->isFranchise() || $this->isSuperFrnachise()) && $stockDetails[0]->req_type != '2') {
            # code...
            $productList = $this->Product_model->getOrderProductsRelationalFran($stockDetails[0]->id);
            $packageList = $this->Product_model->getOrderPackagesRelationalFran($stockDetails[0]->id);
            $data = [
                'currentPage'       => empty($_SERVER['HTTP_REFERER']) ? 'javascript:void(0)' : $_SERVER['HTTP_REFERER'],
                'pageName'          => 'Stock details',
                'productList' => $productList,
                'packageList' => $packageList,
                'stockDetails' => $stockDetails,
            ];
            
            return $this->adminView('stock-req-details', 'Products/Stocks', $data);
        } elseif ($this->isAdmin() || $this->isSuperAdmin() || $this->isSuperFrnachise()) {
            # code...
            $productList = $this->Product_model->getOrderProductsRelationalFran($stockDetails[0]->id);
            foreach($productList as $key=>$value){
                $value->adminStock = $this->Product_model->getOrderProductsstockinadmin($value->product_id)[0]->stocks;
            }
            
            
            $packageList = $this->Product_model->getOrderPackagesRelationalFran($stockDetails[0]->id);
            foreach($packageList as $key=>$value){
                $value->adminStock = $this->Product_model->getOrderProductsstockinadmin($value->package_id,'package')[0]->stocks;
            }
            
            $data = [
                'currentPage'       => empty($_SERVER['HTTP_REFERER']) ? 'javascript:void(0)' : $_SERVER['HTTP_REFERER'],
                'pageName'          => 'Stock details',
                'franchise_details' => $this->Common_model->getFranchiseDetailsId($stockDetails[0]->franchise_id),
                'productList' => $productList,
                'packageList' => $packageList,
                'stockDetails' => $stockDetails,
            ];
            // echo "<pre>";
            //     print_r($stockDetails);
            // echo "</pre>";exit;
            // echo "done";exit;
            return $this->adminView('stock-req-details', 'Products/Stocks/Admin', $data);
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function rejectedRequest()
    {
        if ($this->isFranchise() || $this->isSuperFrnachise()) {
            $data = [
                'currentPage'       => '/wp-dashboard/stocks/accepted-list',
                'pageName'          => 'Rejected List',
                'rejected_reports'   => $this->Product_model->getFilterReportByTranStatus($this->session->get('user_id'), '0')
            ];
            return $this->adminView('rejected-list', 'Products/Stocks', $data);
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function stockReports()
    {
        if ($this->isFranchise() || $this->isSuperFrnachise()) {
            $productList = [];
            $packageList = [];
            $productInvStocks = $this->Product_model->getAllFranchiseInvDetails($this->session->get('user_id'), '1');
            $packageInvStocks = $this->Product_model->getAllFranchiseInvDetails($this->session->get('user_id'), '2');
            if (!empty($productInvStocks)) {
                # code...
                foreach ($productInvStocks as $key => $value) {
                    # code...
                    $productList[$key]['stock_list'] = $value;
                    $productList[$key]['product_list'] = $this->Product_model->getProductDetails($value->product_id)[0];
                }
            }
            if (!empty($packageInvStocks)) {
                # code...
                foreach ($packageInvStocks as $key => $value) {
                    # code...
                    $packageList[$key]['stock_list'] = $value;
                    $packageList[$key]['package_list'] = $this->Product_model->getPackageDetails($value->package_id)[0];
                }
            }

            $data = [
                'currentPage'       => '/wp-dashboard/stocks/stock-reports',
                'pageName'          => 'Stock Reports',
                'productList'       => $productList,
                'packageList'       => $packageList
            ];

            return $this->adminView('stock-reports', 'Products/Stocks', $data);
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function adminFetchFranchiseStockReq()
    {
        if ($this->isAdmin() || $this->isSuperAdmin() || $this->isSuperFrnachise()) {
            # code...
            $data = [
                'currentPage'       => '/wp-dashboard/franchise-settings/stocks/pending-stocks',
                'pageName'          => 'Pending stocks request',
                'pending_request'   => $this->Product_model->getFilterReportByTranStatus(false, '1', $this->getSessions('user_id'))
            ];
            return $this->adminView('pending-stocks', 'Products/Stocks/Admin/', $data);
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function allowStockReq($orderId = '', $transationId = '')
    {
        if ($this->isAdmin() || $this->isSuperAdmin() || $this->isSuperFrnachise()) {
            $stockDetails = $this->Product_model->getFranchiseRequest($transationId, '1');
            if (!empty($stockDetails)) {
                # code...
                $updatedStock = [
                    'current_status'      => '2',
                    'is_notification'     => '0',
                    'updated_userid'      => $this->session->get('user_id'),
                    'cancellation_reason' => null,
                    'updated_date'        => date('Y-m-d H:i:s')
                ];
                $stockStatus = $this->Product_model->updateFranchiseReq($updatedStock, $stockDetails[0]->id);
                if ($stockStatus) {
                    $productList = $this->Product_model->getOrderProductsRelationalFran($stockDetails[0]->id);
                    $packageList = $this->Product_model->getOrderPackagesRelationalFran($stockDetails[0]->id);
                    if (!empty($packageList)) {
                        # code...
                        foreach ($packageList as $key => $value) {
                            # code...
                            $packageInv = $this->Product_model->loadPackageInventory($value->package_id);
                            if ((int)$packageInv[0]->stocks - (int)$value->qty > 0) {
                                # code...
                                $updatePackageData = [
                                    'package_id' => $value->package_id,
                                    'stocks'     => (int)$packageInv[0]->stocks - (int)$value->qty
                                ];
                                if ($this->isSuperFrnachise()) {
                                    # code...
                                    $getCurrentStocks = $this->Product_model->getFranchiseInvDetails($this->getSessions('user_id'), $value->package_id,  '2');
                                    if (empty($getCurrentStocks)) {
                                        # code...
                                        $this->setSessionNotification('wp_page', false, 'error', 'Few of your allowed packages are out of stocks, so not able proceed accessing the stocks.');
                                         $updatedStock = [
                                            'current_status'      => '1',
                                            'is_notification'     => '1',
                                            'updated_userid'      => $this->session->get('user_id'),
                                            'cancellation_reason' => null,
                                            'updated_date'        => date('Y-m-d H:i:s')
                                        ];
                                        $this->Product_model->updateFranchiseReq($updatedStock, $stockDetails[0]->id);
                                        return $this->redirectToUrl('/wp-dashboard/franchise-settings/stocks/pending-request');
                                    } elseif($getCurrentStocks[0]->total_stocks - $value->qty > 0) {
                                        $updatedFranchiseStocks = [
                                            'total_stocks' => $getCurrentStocks[0]->total_stocks - $value->qty ,
                                            'updated_date'   => date('Y-m-d')
                                        ];
                                        $status = $this->Product_model->updateFranchiseInvDetailsByMem($updatedFranchiseStocks, $value->package_id, $this->getSessions('user_id'), '2');
                                    } else {
                                        $this->setSessionNotification('wp_page', false, 'error', 'Few of your allowed packages are out of stocks, so not able proceed accessing the stocks.');
                                        $updatedStock = [
                                            'current_status'      => '1',
                                            'is_notification'     => '1',
                                            'updated_userid'      => $this->session->get('user_id'),
                                            'cancellation_reason' => null,
                                            'updated_date'        => date('Y-m-d H:i:s')
                                        ];
                                        $this->Product_model->updateFranchiseReq($updatedStock, $stockDetails[0]->id);
                                        return $this->redirectToUrl('/wp-dashboard/franchise-settings/stocks/pending-request');
                                    }
                                } else {
                                    $status = $this->Product_model->updatePackageInventory($updatePackageData);
                                }
                                if ($status) {
                                    # code...
                                    $history = [
                                        'package_id'       => $value->package_id,
                                        'stock_type'       => '0',
                                        'stocks_req'       => $value->qty,
                                        'comments'    => 'Added from the  ' . $this->session->get('user_name') . ' which is related to the transation ID ' . $transationId,
                                        'created_date'     => date('Y-m-d H:i:s')
                                    ];
                                    $this->Product_model->insertPackageInvHistory($history);
                                }
                                $franchiseStocks = [
                                    'franchise_id' => $stockDetails[0]->franchise_id,
                                    'package_id'   => $value->package_id,
                                    'stocks'       => $value->qty,
                                    'comments'     =>
                                    'Added from the  ' . $this->session->get('user_name') . ' which is related to the transation ID ' . $transationId,
                                    'created_date'     => date('Y-m-d H:i:s')
                                ];
                                $this->Product_model->insertFranchiseStockDetails($franchiseStocks, '2');
                                $franchiseProductDetails = $this->Product_model->getFranchiseInvDetails($stockDetails[0]->franchise_id, $value->package_id, '2');
                                if (!empty($franchiseProductDetails)) {
                                    # code...
                                    $franchiseStocks = [
                                        'total_stocks'        => (int)$value->qty + (int)$franchiseProductDetails[0]->total_stocks,
                                        'updated_date'  => date('Y-m-d')
                                    ];
                                    $this->Product_model->updateFranchiseInvDetails($franchiseStocks, $franchiseProductDetails[0]->id, '2');
                                } else {
                                    $franchiseStocks = [
                                        'package_id'   =>   $value->package_id,
                                        'franchise_id' => $stockDetails[0]->franchise_id,
                                        'total_stocks' => $value->qty,
                                        'created_date'  => date('Y-m-d H:i:s')
                                    ];
                                    $this->Product_model->insertFranchiseInvDetails($franchiseStocks, '2');
                                }
                            } else {
                                $this->setSessionNotification('wp_page', false, 'error', 'Few of your allowed packages are out of stocks, so not able proceed accessing the stocks.');
                                return $this->redirectToUrl('/wp-dashboard/franchise-settings/stocks/pending-request');
                            }
                        }
                    }

                    if (!empty($productList)) {
                        # code...
                        foreach ($productList as $key => $value) {
                            # code...
                            $productInv = $this->Product_model->loadInventoryByProductId($value->product_id);
                            if ((int)$productInv[0]->stocks - (int)$value->qty > 0) {
                                # code...
                                $updatedProductInvData = [
                                    'product_id' => $value->product_id,
                                    'stocks'     => (int)$productInv[0]->stocks - (int)$value->qty
                                ];
                                if ($this->isSuperFrnachise()) {
                                    # code...
                                    $getCurrentStocks = $this->Product_model->getFranchiseInvDetails($this->getSessions('user_id'), $value->product_id,  '');
                                    if (empty($getCurrentStocks)) {
                                        # code...
                                        $this->setSessionNotification('wp_page', false, 'error', 'Few of your allowed packages are out of stocks, so not able proceed accessing the stocks.');
                                        $updatedStock = [
                                            'current_status'      => '1',
                                            'is_notification'     => '1',
                                            'updated_userid'      => $this->session->get('user_id'),
                                            'cancellation_reason' => null,
                                            'updated_date'        => date('Y-m-d H:i:s')
                                            ];
                                        $this->Product_model->updateFranchiseReq($updatedStock, $stockDetails[0]->id);
                                        return $this->redirectToUrl('/wp-dashboard/franchise-settings/stocks/pending-request');
                                    } elseif ($getCurrentStocks[0]->total_stocks - $value->qty > 0) {
                                        $updatedFranchiseStocks = [
                                            'total_stocks' => $getCurrentStocks[0]->total_stocks - $value->qty,
                                            'updated_date'   => date('Y-m-d')
                                        ];
                                        $status = $this->Product_model->updateFranchiseInvDetailsByMem($updatedFranchiseStocks, $value->product_id, $this->getSessions('user_id'), '');
                                    } else {
                                        $this->setSessionNotification('wp_page', false, 'error', 'Few of your allowed packages are out of stocks, so not able proceed accessing the stocks.');
                                        $updatedStock = [
                                            'current_status'      => '1',
                                            'is_notification'     => '1',
                                            'updated_userid'      => $this->session->get('user_id'),
                                            'cancellation_reason' => null,
                                            'updated_date'        => date('Y-m-d H:i:s')
                                        ];
                                        $this->Product_model->updateFranchiseReq($updatedStock, $stockDetails[0]->id);
                                        return $this->redirectToUrl('/wp-dashboard/franchise-settings/stocks/pending-request');
                                    }
                                } else {
                                    $status = $this->Product_model->updateInventory($updatedProductInvData);
                                }
                                if ($status) {
                                    # code...
                                    $history = [
                                        'product_id'    => $value->product_id,
                                        'inventory_status' => '0',
                                        'added_stocks'     => $value->qty,
                                        'remove_reason'    => 'Added from the  ' . $this->session->get('user_name') . ' which is related to the transation ID ' . $transationId,
                                        'created_date'     => date('Y-m-d H:i:s')
                                    ];
                                    $this->Product_model->insertInventoryHistory($history);
                                }
                                $franchiseStocks = [
                                    'franchise_id' => $stockDetails[0]->franchise_id,
                                    'product_id'   => $value->product_id,
                                    'stocks'       => $value->qty,
                                    'comments'     =>
                                    'Added from the  ' . $this->session->get('user_name') . ' which is related to the transation ID ' . $transationId,
                                    'created_date'     => date('Y-m-d H:i:s')
                                ];
                                $this->Product_model->insertFranchiseStockDetails($franchiseStocks, '1');
                                $franchiseProductDetails = $this->Product_model->getFranchiseInvDetails($stockDetails[0]->franchise_id, $value->product_id, '1');
                                if (!empty($franchiseProductDetails)) {
                                    # code...
                                    $franchiseStocks = [
                                        'total_stocks'        => (int)$value->qty + (int)$franchiseProductDetails[0]->total_stocks,
                                        'updated_date'  => date('Y-m-d')
                                    ];
                                    $this->Product_model->updateFranchiseInvDetails($franchiseStocks, $franchiseProductDetails[0]->id, '1');
                                } else {
                                    $franchiseStocks = [
                                        'product_id'   =>   $value->product_id,
                                        'franchise_id' => $stockDetails[0]->franchise_id,
                                        'total_stocks' => $value->qty,
                                        'created_date'  => date('Y-m-d H:i:s')
                                    ];
                                    $this->Product_model->insertFranchiseInvDetails($franchiseStocks, '1');
                                }
                            } else {
                                $this->setSessionNotification('wp_page', false, 'error', 'Few of your allowed product are out of stocks, so not able proceed accessing the stocks.');
                                return $this->redirectToUrl('/wp-dashboard/franchise-settings/stocks/pending-request');
                            }
                        }
                    }
                    $notificationData = [
                        'user_id'  => $stockDetails[0]->franchise_id,
                        'message'  => 'One of your requested stocks has been appproved by the Admin.',
                        'read_status' => '1',
                        'redirect_url' => '/wp-dashboard/stocks/stock-reports',
                    ];
                    $this->Common_model->pushAppNotifications($notificationData);
                    $this->generateInvoiceForFranchiseAccess($stockDetails[0]);
                    $franchiseDetails =  $this->Common_model->getFranchiseDetailsId($stockDetails[0]->franchise_id);
                    $mailData = [
                        'from_name' => 'Deltavo',
                        'to_email'  =>  $franchiseDetails[0]->email,
                        'subject'   =>  'Your ' . $transationId . 'has been approved by the admin.',
                        'mailType'  =>  'product_order',
                        'userArray' =>  [
                            'user_name' =>  $franchiseDetails[0]->user_name,
                            'mobile_header' => 'You requested stocks has been approved.',
                            'message'   => "We glad to inform you that the requested stock with the transation id $transationId has been approved. And your stocks are updated successfully."
                        ]
                    ];
                    $this->sendEmail($mailData, 2);
                    $this->setSessionNotification('wp_page', true, 'success', 'All permissions are updated successfully with this transation id.');
                    return $this->redirectToUrl('/wp-dashboard/franchise-settings/stocks/accepted-request');
                }
                $this->setSessionNotification('wp_page', false, 'error', 'Something went wrong. Please try again later.');
                return $this->redirectToUrl('/wp-dashboard/franchise-settings/stocks/pending-request');
            } else {
                $this->setSessionNotification('wp_page', false, 'error', 'Invalid details given. please try again later.');
                return $this->redirectToUrl('/wp-dashboard/franchise-settings/stocks/pending-request');
            }
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function adminAcceptedFranchiseStockReq()
    {
        if ($this->isAdmin() || $this->isSuperAdmin() || $this->isSuperFrnachise()) {
            $order_details = $this->Product_model->getFilterReportByTranStatus(false, '23', $this->getSessions('user_id'));
            
            foreach($order_details as $key=>$orderValue){
                
                $orderValue->invoiceID = (!empty($this->Product_model->getInvoiceBytransactionId($orderValue->id)))?$this->Product_model->getInvoiceBytransactionId($orderValue->id)[0]->invoice_id:"Not found";
                $orderProduct = $this->Product_model->gettotalQTYBytransactionId($orderValue->id,'1');
                $orderPackage = $this->Product_model->gettotalQTYBytransactionId($orderValue->id,'2');
                if(!empty($orderProduct)){
                    $orderValue->product = $orderProduct;
                    
                }
                if(!empty($orderPackage)){
                    $orderValue->product = $orderPackage;
                    
                }
                
            }
           
            $data = [
                'currentPage'       => '/wp-dashboard/franchise-settings/stocks/accepted-stocks',
                'pageName'          => 'accepted stocks request',
                'pending_request'   => $order_details,
                
            ];
            // print_r($data);exit;
            return $this->adminView('accepted-stocks', 'Products/Stocks/Admin/', $data);
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function adminRejecteedStocksReq()
    {
        if ($this->isAdmin() || $this->isSuperAdmin() || $this->isSuperFrnachise()) {
            $data = [
                'currentPage'       => '/wp-dashboard/franchise-settings/stocks/rejected-stocks',
                'pageName'          => 'Rejected stocks request',
                'pending_request'   => $this->Product_model->getFilterReportByTranStatus(false, '0', $this->getSessions('user_id'))
            ];
            return $this->adminView('rejected-stocks', 'Products/Stocks/Admin/', $data);
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function rejectStockRequest()
    {
        if ($this->isAdmin() || $this->isSuperAdmin() || $this->isSuperFrnachise()) {
            $transationId = $this->request->getPost('transation_id');
            $stockDetails = $this->Product_model->getFranchiseRequest($transationId, '1');
            if (!empty($stockDetails)) {
                $updatedStock = [
                    'current_status'      => '0',
                    'is_notification'     => '0',
                    'updated_userid'      => $this->session->get('user_id'),
                    'cancellation_reason' => $this->request->getPost('reject_name'),
                    'updated_date'        => date('Y-m-d H:i:s')
                ];
                $stockStatus = $this->Product_model->updateFranchiseReq($updatedStock, $stockDetails[0]->id);
                if (($stockStatus)) {
                    $notificationData = [
                        'user_id'  => $stockDetails[0]->franchise_id,
                        'message'  => 'One of your stock request has been rejected by the Admin.',
                        'read_status' => '1',
                        'redirect_url' => '/wp-dashboard/stocks/rejected-reports',
                    ];
                    $this->Common_model->pushAppNotifications($notificationData);
                    $franchiseDetails =  $this->Common_model->getFranchiseDetailsId($stockDetails[0]->franchise_id);
                    $mailData = [
                        'from_name' => 'Deltavo',
                        'to_email'  =>  $franchiseDetails[0]->email,
                        'subject'   =>  'Your ' . $transationId . ' has been rejected by the admin.',
                        'mailType'  =>  'product_order',
                        'userArray' =>  [
                            'user_name' =>  $franchiseDetails[0]->user_name,
                            'mobile_header' => 'You requested stocks has been rejected.',
                            'message'   => "Sorry! your stock request has been rejected by the " . COMPANY_NAME . " Team. <br> The reason behind is - " . htmlspecialchars($this->request->getPost('reject_name'))
                        ]
                    ];
                    $this->sendEmail($mailData, 2);
                    $this->setSessionNotification('wp_page', true, 'success', 'All permissions are updated successfully with this transation id.');
                    return $this->redirectToUrl('/wp-dashboard/franchise-settings/stocks/rejected-stocks');
                } else {
                    $this->setSessionNotification('wp_page', false, 'error', 'Something went wrong. Please try again later.');
                    return $this->redirectToUrl('/wp-dashboard/franchise-settings/stocks/pending-request');
                }
            } else {
                $this->setSessionNotification('wp_page', false, 'error', 'Invalid details given. please try again later.');
                return $this->redirectToUrl('/wp-dashboard/franchise-settings/stocks/pending-request');
            }
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function stockInvHistory($reqId = '')
    {
        if ($this->isFranchise() || $this->isSuperFrnachise()) {
            # code...
            $type = $this->request->getGet('type');
            if ($type === 'product') {
                # code...
                $productDetails = $this->Product_model->getProductDetails($reqId);
                $inventoryList = $this->Product_model->getFranchiseStocksDetails($this->session->get('user_id'), $reqId, '1');
                $data = [
                    'currentPage'       => '/wp-dashboard/stocks/stock-reports',
                    'pageName'          => 'Product stock history',
                    'productDetails'    => $productDetails,
                    'inventoryList'     => $inventoryList,
                    'type' =>           $type
                ];
                return $this->adminView('Inventory-history', 'Products/Orders/', $data);
            } elseif ($type === 'package') {
                # code...
                $packageDetails = $this->Product_model->getPackageDetails($reqId);
                $inventoryList = $this->Product_model->getFranchiseStocksDetails($this->session->get('user_id'), $reqId, '2');
                $data = [
                    'currentPage'       => '/wp-dashboard/stocks/stock-reports',
                    'pageName'          => 'Package stock history',
                    'packageDetails'    => $packageDetails,
                    'inventoryList'     => $inventoryList,
                    'type' =>           $type
                ];
                return $this->adminView('Inventory-history', 'Products/Orders/', $data);
            } else {
                $this->setSessionNotification('wp_page', false, 'error', 'Invalid details given. please try again later.');
                return $this->redirectToUrl('/wp-dashboard/stocks/stock-reports');
            }
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function loadReports()
    {
        
        if ($this->isFranchise() || $this->isSuperFrnachise()) {
            if($this->request->getGet('apply_filter') == 'true') { 
                $fromDate = $this->request->getGet('from_date');
                $toDate = $this->request->getGet('to_date');
                if ($toDate < $fromDate) {
                    # code...
                    $this->setSessionNotification('wp_page', true, 'error', 'Invalid Date Range is Given in Input');
                    return $this->redirectToUrl('/wp-dashboard/invoice/reports');
                } else {
                    $loadReports = $this->Product_model->getInvoiceRecordsFilterByDate('2', $this->getSessions('user_id'), $fromDate, $toDate);
                }
            } else {
                $loadReports = $this->Product_model->getInvoiceRecords('', '23', $this->getSessions('user_id'));
                
            }
            foreach ($loadReports as $key => $value) {
                # code...
                $value->totalQty = 0;
                $value->totalBV = 0;
                $value->totalDP = 0;
                $packageList = $this->Product_model->getOrderPackagesInv($value->order_id);
                $productList = $this->Product_model->getOrderProductsInv($value->order_id);
                if (!empty($productList)) {
                    # code...
                    foreach ($productList as $Pkey => $Pvalue) {
                        # code...
                     if($Pvalue->total_quantity != ''){
                            $value->totalQty += (int)($Pvalue->qty);
                            $value->totalBV += (int)($Pvalue->qty)*(float)($Pvalue->bv_amout);
                            $value->totalDP += (float)($Pvalue->dp_amount)*(int)($Pvalue->qty);
                        }
                        else{
                            $value->totalQty += (int)($Pvalue->qty);
                            $value->totalBV += (float)($Pvalue->bv_amout)*(int)($Pvalue->qty);
                            $value->totalDP += (float)($Pvalue->dp_amount)*(int)($Pvalue->qty);
                        }
                    }
                    
                    
                }

                if (!empty($packageList)) {
                    # code...
                    foreach ($packageList as $cKey => $Cvalue) {
                        # code...
                        
                        if($Cvalue->total_quantity != ''){
                            
                            $value->totalQty += (int)($Cvalue->qty);
                            $value->totalBV += (int)($Cvalue->qty)*(float)($Cvalue->bv_amout);
                            $value->totalDP += (int)($Cvalue->qty)*(float)($Cvalue->dp_amount);
                        }
                        else{
                            $value->totalQty += (int)($Cvalue->qty);
                            $value->totalBV += (float)($Cvalue->bv_amout)*(int)($Cvalue->qty);
                            $value->totalDP += (float)($Cvalue->dp_amount)*(int)($Cvalue->qty);
                        }
                        
                    }
                    
                }
                $memberDetails = $this->Common_model->getCustomMemSelectDataByUserId($value->mem_id,'current_level, full_name, members_id, ')[0];
                $value->current_level = $memberDetails->current_level;
                $value->full_name = $memberDetails->full_name;
                $value->members_id = $memberDetails->members_id;
                $value->net_amount = $value->net_amount + $value->total_cgst + $value->total_sgst + $value->total_igst;
            }
            
            $data = [
                'currentPage'         => '/wp-dashboard/invoice/reports',
                'pageName'            => 'Reports',
                'transation_list'     => $loadReports
            ];
            
            return $this->adminView('order-reports', 'Products/Orders/', $data);
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function loadRefunds()
    {
        if ($this->isFranchise() || $this->isSuperFrnachise()) {
            if($this->request->getGet('apply_filter') == 'true') { 
                $fromDate = $this->request->getGet('from_date');
                $toDate = $this->request->getGet('to_date');
                if ($toDate < $fromDate) {
                    # code...
                    $this->setSessionNotification('wp_page', true, 'error', 'Invalid Date Range is Given in Input');
                    return $this->redirectToUrl('/wp-dashboard/invoice/refund');
                } else {
                    $loadReports = $this->Product_model->getInvoiceRecordsFilterByDate('3', $this->getSessions('user_id'), $fromDate, $toDate);
                }
            } else {
                $loadReports = $this->Product_model->getInvoiceRecords('', '3', $this->getSessions('user_id'));
            }
            foreach ($loadReports as $key => $value) {
                # code...
                $value->totalQty = 0;
                $value->totalBV = 0.00;
                $value->totalDP = 0.00;
                $packageList = $this->Product_model->getOrderPackagesInv($value->order_id);
                $productList = $this->Product_model->getOrderProductsInv($value->order_id);
                
                if (!empty($productList)) {
                    # code...
                    foreach ($productList as $Pkey => $Pvalue) {
                        # code...
                        if($Pvalue->total_quantity != ''){
                            $value->totalQty += (int)($Pvalue->total_quantity)-(int)($Pvalue->qty);
                            $value->totalBV += ((int)($Pvalue->total_quantity)-(int)($Pvalue->qty))*(float)($Pvalue->bv_amout);
                            $value->totalDP += ((int)($Pvalue->total_quantity)-(int)($Pvalue->qty))*(float)($Pvalue->dp_amount);
                            // $value->totalBV += (float)($Pvalue->bv_amout)*(int)($Pvalue->qty);
                        }
                        else{
                            $value->totalQty += (int)($Pvalue->qty);
                            $value->totalBV += (float)($Pvalue->bv_amout)*(int)($Pvalue->qty);
                            $value->totalDP += ((int)($Pvalue->total_quantity)-(int)($Pvalue->qty))*(float)($Pvalue->dp_amount);
                        }
                    }
                }

                if (!empty($packageList)) {
                    # code...
                    foreach ($packageList as $cKey => $Cvalue) {
                        # code...
                        if($Cvalue->total_quantity != ''){
                            $value->totalQty += (int)($Cvalue->total_quantity)-(int)($Cvalue->qty);
                            $value->totalBV += ((int)($Cvalue->total_quantity)-(int)($Cvalue->qty))*(float)($Cvalue->bv_amout);
                            $value->totalDP += ((int)($Cvalue->total_quantity)-(int)($Cvalue->qty))*(float)($Cvalue->dp_amount);
                        }
                        else{
                            $value->totalQty += (int)($Cvalue->qty);
                            $value->totalBV += (float)($Cvalue->bv_amout)*(int)($Cvalue->qty);
                            $value->totalDP += ((int)($Cvalue->total_quantity)-(int)($Cvalue->qty))*(float)($Cvalue->dp_amount);
                        }
                    }
                }
                
                
                $memberDetails = $this->Common_model->getCustomMemSelectDataByUserId($value->mem_id, 'current_level,mobile_no, full_name, members_id,tracking_id ')[0];
                $memberDetailsSponser = $this->Common_model->getCustomMemSelectDataByMemId($memberDetails->tracking_id, 'full_name')[0];
                $value->current_level = $memberDetails->current_level;
                $value->full_name = $memberDetails->full_name;
                $value->members_id = $memberDetails->members_id;
                $value->mobile_no = $memberDetails->mobile_no;
                $value->sponser_id = $memberDetails->tracking_id;
                $value->sponser_name = $memberDetailsSponser->full_name;
                $value->net_amount = $value->net_amount + $value->total_cgst + $value->total_sgst + $value->total_igst;
                
            }

            $filterNormalRefund = array_filter($loadReports, function ($value) {
                $invoiceDate = date('Y-m-d', strtotime($value->created_date));
                $refundedDate = date('Y-m-d', strtotime($value->refunded_date));
                $after90Days = date('Y-m-d', strtotime($invoiceDate . " + 19 days"));
                if ($refundedDate < $after90Days) {
                    return $value;
                }
            });
            $filterBuyBackRefund = array_filter($loadReports, function ($value) {
                $invoiceDate = date('Y-m-d', strtotime($value->created_date));
                $refundedDate = date('Y-m-d', strtotime($value->refunded_date));
                $after90Days = date('Y-m-d', strtotime($invoiceDate . " + 19 days"));
                if ($refundedDate > $after90Days) {
                    return $value;
                }
            });
              
            $data = [
                'currentPage'         => '/wp-dashboard/invoice/Refund',
                'pageName'            => 'Refund',
                'transation_list'     => $loadReports,
                'normal_refund'       => array_values($filterNormalRefund),
                'buy_back_refund'     => array_values($filterBuyBackRefund)
            ];
            return $this->adminView('order-refund', 'Products/Orders/', $data);
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function newOrderInv()
    {
        if ($this->isFranchise() || $this->isSuperFrnachise()) {
            $productList = [];
            $packageList = [];
            $franchiseId = $this->session->get('user_id');
            $productList = $this->Product_model->getFranchiseAccessProducts($franchiseId);
            $packageList = $this->Product_model->getFranchiseAccessPackages($franchiseId);
            $data = [
                'currentPage'         => '/wp-dashboard/invoice/new-order',
                'pageName'            => 'New Order',
                'productList'    => $productList,
                'packageList'    => $packageList,
            ];
            return $this->adminView('new-order-franchise', 'Products/Orders/', $data);
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function placeDirectOrder()
    {
        
        
        if ($this->isFranchise() || $this->isSuperFrnachise()) {
            $totalBV = $totalDP = $totalMRP = 0;
            $franchiseAccount = $this->Common_model->getFranchiseKYCStatus($this->getSessions('user_id'));
            if (empty($franchiseAccount)) {
                $this->setSessionNotification('wp_page', false, 'error', 'You need to add your bank account to continue buying the order.');
                return $this->redirectToUrl('wp-dashboard/invoice/reports');
            } elseif (!$franchiseAccount[0]->gst_no) {
                $this->setSessionNotification('wp_page', false, 'error', 'You need to set the GST number');
                return $this->redirectToUrl('wp-dashboard/invoice/reports');
            }
            $orderId = $this->getAutoGeneratedOrderId();
            $productList = $this->request->getPost('product');
            $packageList = $this->request->getPost('package');
            $franchiseId = $this->session->get('user_id');
            $transId = $this->getMemberTransationID();
            $memId =  $this->Common_model->getMemberDetailsByMemid($this->request->getPost('mem_id'))[0]->user_id;
            $orderData = [
                'order_id'      => $orderId,
                'mem_id'        => $memId,
                'is_direct_order' => '1',
                'franchise_id'  => $franchiseId,
                'state_id'      => $this->Common_model->getFranchiseDetails($franchiseId)[0]->state,
                'transation_id' => $transId,
                'transport_by'  => $this->request->getPost('transport_by'),
                'payment_mode'  => $this->request->getPost('payment_mode'),
                'remarks'       => $this->request->getPost('remarks'),
                'order_status'  => '2',
                'created_date'  => date('Y-m-d H:i:s')
            ];
            $totalOrderBVThisMonth =  $this->Common_model->getMemberCurrentMonthBV($memId)[0]->total_bv ? $this->Common_model->getMemberCurrentMonthBV($memId)[0]->total_bv : 0;
            $lastOrderId = $this->Product_model->insertUserOrders($orderData);
            if ($lastOrderId) {
                # code...
                if (!empty($productList)) {
                    # code...
                    foreach ($productList as $key => $value) {
                        # code...
                        $productInv = $this->Product_model->getFranchiseInvDetails($franchiseId, $value['product_id'], '1');
                        $productList = $this->Product_model->getProductDetails($value['product_id']);
                        if (!empty($productInv)) {
                            # code...
                            $productData = [
                                'order_id'   => $lastOrderId,
                                'product_id' => $value['product_id'],
                                'total_quantity' => $value['qty'],
                                'qty'        => $value['qty'],
                                'bv_amout'   => $productList[0]->bv_bonous,
                                'dp_amount'  => $productList[0]->dp,
                                'mrp_amount' => $productList[0]->mrp
                            ];
                            $totalBV  = $totalBV + ((float)$productList[0]->bv_bonous * $value['qty']);
                            $totalDP  = $totalDP + ((float)$productList[0]->dp * $value['qty']);
                            $totalMRP = $totalMRP + ((float)$productList[0]->mrp * $value['qty']);
                            $this->Product_model->insertOrderProducts($productData);
                            $qty = (int)$productInv[0]->total_stocks  - (int)$value['qty'];
                            if ($qty < 0) {
                                # code...
                                $this->setSessionNotification('wp_page', false, 'error', 'Few of your ordered product has been out of stocks. Please make the item to instocks continuing the order.');
                                return $this->redirectToUrl('wp-dashboard/products/orders-list');
                            }
                            $updateInvData = [
                                'total_stocks'   => $qty,
                                'updated_date'   => date('Y-m-d')
                            ];
                            $status = $this->Product_model->updateFranchiseInvDetailsByMem($updateInvData, $value['product_id'], $franchiseId, '1');
                            if ($status) {
                                # code...
                                $insertFranchiseInvHis = [
                                    'franchise_id' => $franchiseId,
                                    'product_id'   => $value['product_id'],
                                    'stock_type'   => '0',
                                    'stocks'        =>  $value['qty'],
                                    'comments'     => "Transaction ".$transId.' has been placed by.',
                                    'created_date'  => date('Y-m-d H:i:s')
                                ];
                                $this->Product_model->insertFranchiseStockDetails($insertFranchiseInvHis, '1');
                            }
                        }
                    }
                }
                if (!empty($packageList)) {
                    # code...
                    foreach ($packageList as $key => $value) {
                        # code...
                        $packageInv  = $this->Product_model->getFranchiseInvDetails($franchiseId, $value['package_id'], '2');
                        $packageList = $this->Product_model->getPackageDetails($value['package_id']);
                        if (!empty($packageInv)) {
                            # code...
                            $packageData = [
                                'order_id'      => $lastOrderId,
                                'package_id'    => $value['package_id'],
                                'total_quantity' => $value['qty'],
                                'qty'           => $value['qty'],
                                'bv_amout'      => $packageList[0]->bv_bonous,
                                'dp_amount'     => $packageList[0]->dp,
                                'mrp_amount'    => $packageList[0]->mrp
                            ];
                            $totalBV  = $totalBV + ((float)$packageList[0]->bv_bonous * $value['qty']);
                            $totalDP  = $totalDP + ((float)$packageList[0]->dp * $value['qty']);
                            $totalMRP = $totalMRP + ((float)$packageList[0]->mrp * $value['qty']);
                            $this->Product_model->insertOrderPackages($packageData);
                            $qty = (int)$packageInv[0]->total_stocks  - (int)$value['qty'];
                            if ($qty < 0) {
                                # code...
                                $this->setSessionNotification('wp_page', false, 'error', 'Few of your ordered package has been out of stocks. Please make the item to instocks continuing the order.');
                                return $this->redirectToUrl('wp-dashboard/products/orders-list');
                            }
                            $updateInvData = [
                                'total_stocks'   => $qty,
                                'updated_date'   => date('Y-m-d')
                            ];
                            $status = $this->Product_model->updateFranchiseInvDetailsByMem($updateInvData, $value['package_id'], $franchiseId, '2');
                            if ($status) {
                                # code...
                                $insertFranchiseInvHis = [
                                    'franchise_id' => $franchiseId,
                                    'package_id'   => $value['package_id'],
                                    'stock_type'   => '0',
                                    'stocks'        =>  $value['qty'],
                                    'comments'     =>$orderId.' has been placed by.',
                                    'created_date'  => date('Y-m-d H:i:s')
                                ];
                                $this->Product_model->insertFranchiseStockDetails($insertFranchiseInvHis, '2');
                            }
                        }
                    }
                }
                $updateOrder = [
                    'total_bv' => $totalBV,
                    'total_dp' => $totalDP,
                    'total_mrp' => $totalMRP,
                    'updated_date'   => date('Y-m-d H:i:s')
                ];
                $getFranchiseDetails = $this->Common_model->getFranchiseDetailsId($this->session->get('user_id'));
                $franchiseData = [
                    'available_credits' => $getFranchiseDetails[0]->available_credits - (float)($totalDP)
                ];
                $this->Common_model->updateFranchiseAcess(false, $this->session->get('user_id'), $franchiseData);

                $pStatus = $this->Product_model->updateCustomerOrder($updateOrder, $lastOrderId);
                if ($pStatus) {
                    # code...
                    $notificationData = [
                        'user_id'  => $this->request->getPost('mem_id'),
                        'message'  => "One of your order $orderId has been accepted and a Invoice on the order successfully generated.",
                        'read_status' => '1',
                        'redirect_url' => '/wp-dashboard/invoice/member-invoice'
                    ];
                    $this->Common_model->pushAppNotifications($notificationData);
                    $orderDetails = $this->Product_model->getOrderByOrderId($orderId);
                    
                    # Getting Member Details selected people
                    $memberDetails = $this->Common_model->getCustomMemSelectDataByUserId($orderDetails[0]->mem_id, 'current_level,full_name,total_bv,tracking_id,milstone_bv, tracking_id, members_id, accumulated_bv, promotion_date, user_id')[0];

                    $extraBonous = $this->Common_model->getExtraBonous($orderDetails[0]->mem_id);
                    $extraBonousAmount = !empty($extraBonous) ? $extraBonous[0]->added_bv : 0.00;
                    # Promotional settings
                    $this->setPerformanceBonousIncome($memberDetails, $orderDetails[0]);
                    $this->generateInvoiceOrderUserId($orderDetails[0]);
                    # Getting all the past promoted position for the requested member
                    $promotionDataByUserId = $this->Common_model->getPromotionalCustomer($orderDetails[0]->mem_id);
                    # Getting all memebers details
                    $getAllMembers = $this->Common_model->getAllFilerMembers($orderDetails[0]->mem_id);
                    // Logice for promotional behaviour

                    $myUplines = [];
                    $myDownlineTotal = $this->loadMyDownLinesTotal($memberDetails->members_id, $memberDetails->tracking_id);
                    foreach ($getAllMembers as $key => $value) {
                        # code...
                        if ($value->members_id === $memberDetails->tracking_id) {
                            # code...
                            array_push($myUplines, $value);
                            array_splice(
                                $getAllMembers,
                                $key,
                                1
                            );
                        }
                    }
                    if (!empty($myUplines)) {
                        # code...
                        $this->getAllUperLineMem($myUplines, $getAllMembers); // Getting my upline members 
                    }

                    // $orderDetails[0]->total_bv = 80;
                    // echo 'This month BV => ' . $totalOrderBVThisMonth . "<br>";
                    // $promotionDataByUserId = [];
                    // $myDownlineTotal = 0;
                    // $memberDetails->current_level = '2';
                    // $memberDetails->total_bv = 70;
                    $currentLevel = $memberDetails->current_level;
                    $mileStone = (float)$memberDetails->milstone_bv;
                    $totalBVAmount = $bvAmount = (float)$memberDetails->total_bv +  (float)$orderDetails[0]->total_bv + $myDownlineTotal + $extraBonousAmount;
                    $totalDownlineAmount = (float)$orderDetails[0]->total_bv + $myDownlineTotal + $totalOrderBVThisMonth + $extraBonousAmount;
                    $mileStoneAmount = (float)$memberDetails->milstone_bv;
                    $currentDate = date('Y-m-d');
                    $accumulatedBV = $memberDetails->accumulated_bv ? $memberDetails->accumulated_bv : 0;
                    if ($currentDate > $memberDetails->promotion_date) {
                        // echo $currentDate . ">" . $memberDetails->promotion_date;
                        $accumulatedBV = ($accumulatedBV + $orderDetails[0]->total_bv);
                    }
                    $d = new DateTime(date('Y-m-d'));
                    $d->modify('first day of next month');
                    if (empty($promotionDataByUserId)) {
                        # code... This mean the member have not purchase any product amout before
                        if (((float)$memberDetails->total_bv <= 0 && $memberDetails->current_level == '1' && (float)$orderDetails[0]->total_bv >= 25) || ($memberDetails->current_level == '1' && $totalBVAmount >= 25 )) {
                            # code... Logic for the Business Leader Promotion
                            // echo $orderDetails[0]->total_bv . '1=>' . $totalDownlineAmount;
                            
                            $currentLevel = '2';
                            $promotionData = [
                                'user_id' => $orderDetails[0]->mem_id,
                                'transation_id'   => $transId,
                                'current_level'   => $currentLevel,
                                'added_bv' => $orderDetails[0]->total_bv,
                                'created_date' => date('Y-m-d H:i:s'),
                            ];
                            $promotionHistory = [
                                'user_id' => $orderDetails[0]->mem_id,
                                'promtion_type' => '0',
                                'promotion_label' =>   $currentLevel,
                                'last_lebel' =>   '1',
                                'created_date' => date('Y-m-d H:i:s')
                            ];
                            $updateUserData = [
                                'current_level' => '2',
                                'promotion_date'=> date('Y-m-d'),
                                'updated_date'   => date('Y-m-d'),
                            ];
                            $this->Common_model->insertPromotionHistory($promotionHistory);
                            $this->Product_model->insertPromotionRecords($promotionData);
                            $this->Common_model->updateMemberAccess(false, $orderDetails[0]->mem_id, $updateUserData);
                            foreach ($this->globalUplineMember as $key => $value) {
                                # code...
                                if ($value->current_level == '1') {
                                    # code...
                                    $updatemultiUserData = [
                                        'current_level' => '2',
                                        'promotion_date'=> date('Y-m-d'),
                                        'updated_date'   => date('Y-m-d'),
                                    ];
                                    $this->Common_model->updateMemberAccess(false, $value->user_id, $updatemultiUserData);
                                    $promotionHistory = [
                                        'user_id' => $value->user_id,
                                        'promtion_type' => '0',
                                        'last_lebel' =>   '1',
                                        'promotion_label' =>   $currentLevel,
                                        'created_date' => date('Y-m-d H:i:s')
                                    ];
                                    $this->Common_model->insertPromotionHistory($promotionHistory);
                                }
                            }
                            $mileStone = 25;
                        } 
                        
                    } 
                    
                    else {
                        
                        if (((float)$totalDownlineAmount >= 25 && (int)$memberDetails->current_level < 2) || ($totalBVAmount >= 25 && (int)$memberDetails->current_level < 2)) {
                           $currentLevel = '2';
                            $promotionData = [
                                'user_id' => $orderDetails[0]->mem_id,
                                'transation_id'   => $transId,
                                'current_level'   => $currentLevel,
                                'added_bv' => $orderDetails[0]->total_bv,
                                'created_date' => date('Y-m-d H:i:s'),
                            ];
                            $promotionHistory = [
                                'user_id' => $orderDetails[0]->mem_id,
                                'promtion_type' => '0',
                                'last_lebel' =>   '1',
                                'promotion_label' =>   $currentLevel,
                                'created_date' => date('Y-m-d H:i:s')
                            ];
                            $updateUserData = [
                                'current_level' => '2',
                                'promotion_date'=> date('Y-m-d'),
                                'updated_date'   => date('Y-m-d'),
                            ];
                            $this->Common_model->insertPromotionHistory($promotionHistory);
                            $this->Product_model->insertPromotionRecords($promotionData);
                            $this->Common_model->updateMemberAccess(false, $orderDetails[0]->mem_id, $updateUserData);
                            foreach ($this->globalUplineMember as $key => $value) {
                                # code...
                                if ($value->current_level == '1') {
                                    # code...
                                    $updatemultiUserData = [
                                        'current_level' => '2',
                                        'promotion_date'=> date('Y-m-d'),
                                        'updated_date'   => date('Y-m-d'),
                                    ];
                                    $this->Common_model->updateMemberAccess(false, $value->user_id, $updatemultiUserData);
                                    $promotionHistory = [
                                        'user_id' => $value->user_id,
                                        'promtion_type' => '1',
                                        'promotion_label' =>   $currentLevel,
                                        'created_date' => date('Y-m-d H:i:s')
                                    ];
                                    $this->Common_model->insertPromotionHistory($promotionHistory);
                                }
                            }
                            $mileStone = 25;
                        }   
                    }
                    
                    $newLevel = '1';
                    if ($currentLevel == '2'){ $newLevel = $currentLevel;}
                    elseif ($totalBVAmount >= 25 &&( $memberDetails->current_level == '1')) {$newLevel = '2';}
                    else {$newLevel = $memberDetails->current_level;}
                    if (!empty($this->Common_model->getCurrentMonthPromotionCount($orderDetails[0]->mem_id))) {
                        $accumulatedBV = 0;
                    }
                    $updateUserTerminal = [
                        'total_bv'  => $totalBVAmount - $myDownlineTotal,
                        'milstone_bv' => $mileStone,
                        'current_level'   => $newLevel,
                        'upgrade_level' => $currentLevel,
                        'is_direct_upgrade' => '1',
                        'updated_date'   => date('Y-m-d'),
                        'accumulated_bv' => $accumulatedBV,
                        'promotion_date' => $currentLevel <= $memberDetails->current_level ? null : $d->format('Y-m-d')
                    ];

                    $this->Common_model->updateMemberAccess(false, $orderDetails[0]->mem_id, $updateUserTerminal);
                    $memberDetails->total_bv = $totalBVAmount - $myDownlineTotal;
                    if ($currentLevel == '2') {
                        $this->generatePerformanceIncomeRecordsBySponserClub($memberDetails, $orderDetails, $currentLevel, $totalOrderBVThisMonth);
                    } elseif ($memberDetails->current_level == '2' || $memberDetails->current_level == '3') {
                        $this->generatePerformanceIncomeRecordsBySponserClub($memberDetails, $orderDetails, $memberDetails->current_level, $totalOrderBVThisMonth);
                    }
                    if ($memberDetails->current_level > 3) {
                        # code...
                        $this->generatedPerforManceIncomeRecordsByLeaderShipClub($memberDetails, (float)$orderDetails[0]->total_bv);
                        $this->generatedPerforManceIncomeRecordsByStarShipClub($memberDetails, (float)$orderDetails[0]->total_bv);
                        $this->generatedPerforManceIncomeRecordsByDyanmicClub($memberDetails, (float)$orderDetails[0]->total_bv);
                        $this->generateIncomeRecordsBySpecialClub($memberDetails, $orderDetails[0]);
                        $this->generateIncomeRecordsByTargetClub($memberDetails, $orderDetails[0]);
                    }
                    $this->setSessionNotification('wp_page', true, 'success', 'Your order generated successfully and  the details sent to the Email id.');
                } else {
                    $this->setSessionNotification('wp_page', false, 'error', 'Not able to insert the order. Please try again later.');
                }
            } else {
                $this->setSessionNotification('wp_page', false, 'error', 'Invalid details given. please try again later.');
            }
            // echo "<pre>";
            // print_r ($this->request->getPost());
            // echo "</pre>";
            // exit;
            return $this->redirectToUrl('wp-dashboard/invoice/reports');
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    // Member Invoice Section
    public function selfInvoice()
    {
        if ($this->isMember()) {
            # code...
            $invoiceList = [];
            if ($this->request->getGet('apply_filter') === 'true') {
                $frmDate =  $this->request->getGet('form_date');
                 $m = date('m', strtotime($frmDate));
                 $y = date('Y', strtotime($frmDate));
                 $tDate =  $this->request->getGet('to_date');
                 $tm = date('m', strtotime($tDate));
                 $ty = date('Y', strtotime($tDate));
                 $formDate =  date('Y-m-d', mktime(0, 0, 0, $m, 1,$y)).' 00:00:00';
                 $toDate = date('Y-m-t', mktime(0, 0, 0, $tm, 28,$ty)).' 23:59:59';
                 $invoiceList = $this->Product_model->getMemberAllCompletedOrder($this->getSessions('user_id'),'filter',$formDate,$toDate);
                 
                 if (!empty($invoiceList)) {
                    # code...
                    $invoiceList = array_map(function ($value) {
                        $invoiceDetails = $this->Product_model->getInvoiceDetailsByOrderId($value->id);
                        $value->sponser_name = $this->Common_model->getCustomMemSelectData($value->tracking_id)[0]->full_name;
                        $value->invoice_id   = empty($invoiceDetails) ? 'N/A' : $invoiceDetails[0]->invoice_id;
                        $value->franchise_code = $this->Common_model->getCustomFranchiseDetails($value->franchise_id)[0]->franchise_code;
                        $value->refund_date   = empty($invoiceDetails[0]->refunded_date) ? '' : date('d-m-Y h:i:s a', strtotime($invoiceDetails[0]->refunded_date));
                            
                        return $value;
                    }, $invoiceList);
                }
            }
            else{
                $invoiceList = $this->Product_model->getMemberAllCompletedOrder($this->getSessions('user_id'), 'current');
                if (!empty($invoiceList)) {
                        # code...
                        $invoiceList = array_map(function ($value) {
                            $invoiceDetails = $this->Product_model->getInvoiceDetailsByOrderId($value->id);
                            $orderDetailsPackage = $this->Product_model->getOrderDetailsByOrderId($value->id,'1');
                            $orderDetailsProduct = $this->Product_model->getOrderDetailsByOrderId($value->id,'0');
                            $value->buyBV = 0;
                            $value->buyDP = 0;
                            $value->refundBV = 0;
                            $value->refundDP = 0;
                            $value->refundQty = 0;
                            $value->totalQty = 0;
                            // echo "<pre>";
                            // print_r($orderDetailsPackage);
                            // echo "</pre>";exit;
                            if(!empty($orderDetailsPackage)){
                                foreach ($orderDetailsPackage as $cKey => $Cvalue) {
                                    # code...
                                    
                                    if($Cvalue->total_quantity != ''){
                                        
                                        $value->buyBV += (int)($Cvalue->qty)*(float)($Cvalue->bv_amout);
                                        $value->buyDP += (int)($Cvalue->qty)*(float)($Cvalue->dp_amount);
                                        $value->refundBV += ((int)($Cvalue->total_quantity)-(int)($Cvalue->qty))*(float)($Cvalue->bv_amout);
                                        $value->refundDP += ((int)($Cvalue->total_quantity)-(int)($Cvalue->qty))*(float)($Cvalue->dp_amount);
                                        $value->totalQty += ((int)($Cvalue->total_quantity));
                                        $value->refundQty += ((int)($Cvalue->total_quantity)-(int)($Cvalue->qty));
                                    }
                                    else{
                                        $value->buyBV += (float)($Cvalue->bv_amout)*(int)($Cvalue->qty);
                                        $value->buyDP += (float)($Cvalue->dp_amount)*(int)($Cvalue->qty);
                                        $value->refundBV = "N/A";
                                        $value->refundDP = "N/A";
                                        $value->refundQty = "N/A";
                                    }
                                    
                                }
                    
                            }
                            if(!empty($orderDetailsProduct)){
                                foreach ($orderDetailsProduct as $Pkey => $Pvalue) {
                                    
                                    if($Pvalue->total_quantity != ''){
                                            $value->buyBV += (int)($Pvalue->qty)*(float)($Pvalue->bv_amout);
                                            $value->buyDP += (float)($Pvalue->dp_amount)*(int)($Pvalue->qty);
                                            $value->refundBV += ((int)($Pvalue->total_quantity)-(int)($Pvalue->qty))*(float)($Pvalue->bv_amout);
                                            $value->refundDP += ((int)($Pvalue->total_quantity)-(int)($Pvalue->qty))*(float)($Pvalue->dp_amount);
                                            $value->totalQty += ((int)($Pvalue->total_quantity));
                                            $value->refundQty += ((int)($Pvalue->total_quantity)-(int)($Cvalue->qty));
                                        }
                                    else{
                                            $value->buyBV += (float)($Pvalue->bv_amout)*(int)($Pvalue->qty);
                                            $value->buyDP += (float)($Pvalue->dp_amount)*(int)($Pvalue->qty);
                                            $value->refundBV = "N/A";
                                            $value->refundDP = "N/A";
                                    }
                                }
                            }
                            $value->sponser_name = $this->Common_model->getCustomMemSelectData($value->tracking_id)[0]->full_name;
                            $value->new_invoice   = empty($invoiceDetails) ? '' : $invoiceDetails[0]->new_invoice;
                            $value->franchise_code = $this->Common_model->getCustomFranchiseDetails($value->franchise_id)[0]->franchise_code;
                            $value->refund_date   = empty($invoiceDetails[0]->refunded_date) ? '' : date('d-m-Y h:i:s', strtotime($invoiceDetails[0]->refunded_date));
                            $value->billing_date   =  $invoiceDetails[0]->created_date;
                            return $value;
                        }, $invoiceList);
                    }
            }
            
            $data = [
                'currentPage'         => '/wp-dashboard/invoice/self-invoice',
                'pageName'            => 'Self invoice',
                'invoiceList'         => $invoiceList
            ];
            return $this->adminView('self-invoice', 'Products/Invoice/', $data);
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function teamInvoice()
    {
        if ($this->isMember()) {
            $currentUser = $this->Dashboard_model->getCurrentUser(session()->get('user_status'), session()->get('user_id'));
            
                $myDirects = $currentUser[0]->tracking_id === $currentUser[0]->members_id ? $this->Report_model->getDirectUser($currentUser[0]->tracking_id) : $this->Report_model->getDirectUser($currentUser[0]->members_id, true);
                
                $allMembers = $this->Report_model->getAllNonDirectMem($currentUser[0]->tracking_id);
                foreach ($allMembers as $key => $value) {
                    # code...
                    foreach ($myDirects as $cKey => $cValue) {
                        # code...
                        if ($value->tracking_id === $cValue->members_id) {
                            # code...
                            array_push($myDirects, $value);
                        }
                    }
                }
            
            $invoiceList = [];
            if ($this->request->getGet('apply_filter') === 'true') {
                # code...
                 $frmDate =  $this->request->getGet('form_date');
                 $m = date('m', strtotime($frmDate));
                 $y = date('Y', strtotime($frmDate));
                 $tDate =  $this->request->getGet('to_date');
                 $tm = date('m', strtotime($tDate));
                 $ty = date('Y', strtotime($tDate));
                 $formDate =  date('Y-m-d', mktime(0, 0, 0, $m, 1,$y)).' 00:00:00';
                 $toDate = date('Y-m-t', mktime(0, 0, 0, $tm, 28,$ty)).' 23:59:59';
                 
                    foreach ($myDirects as $key => $value) {
                    $invoiceList = $this->Product_model->getMemberAllCompletedOrder($value->user_id, 'filter',$formDate,$toDate);
                    if (!empty($invoiceList)) {
                        # code...
                        $invoiceList = array_map(function ($value) {
                            $invoiceDetails = $this->Product_model->getInvoiceDetailsByOrderId($value->id);
                            $orderDetailsPackage = $this->Product_model->getOrderDetailsByOrderId($value->id,'1');
                            $orderDetailsProduct = $this->Product_model->getOrderDetailsByOrderId($value->id,'0');
                            $value->buyBV = 0;
                            $value->buyDP = 0;
                            $value->refundBV = 0;
                            $value->refundDP = 0;
                            $value->refundQty = 0;
                            $value->totalQty = 0;
                            if(!empty($orderDetailsPackage)){
                                foreach ($orderDetailsPackage as $cKey => $Cvalue) {
                                    # code...
                                    
                                    if($Cvalue->total_quantity != ''){
                                        
                                        $value->buyBV += (int)($Cvalue->qty)*(float)($Cvalue->bv_amout);
                                        $value->buyDP += (int)($Cvalue->qty)*(float)($Cvalue->dp_amount);
                                        $value->refundBV += ((int)($Cvalue->total_quantity)-(int)($Cvalue->qty))*(float)($Cvalue->bv_amout);
                                        $value->refundDP += ((int)($Cvalue->total_quantity)-(int)($Cvalue->qty))*(float)($Cvalue->dp_amount);
                                        $value->totalQty += ((int)($Cvalue->total_quantity));
                                        $value->refundQty += ((int)($Cvalue->total_quantity)-(int)($Cvalue->qty));
                                    }
                                    else{
                                        $value->buyBV += (float)($Cvalue->bv_amout)*(int)($Cvalue->qty);
                                        $value->buyDP += (float)($Cvalue->dp_amount)*(int)($Cvalue->qty);
                                        $value->refundBV = "N/A";
                                        $value->refundDP = "N/A";
                                    }
                                    
                                }
                    
                            }
                            if(!empty($orderDetailsProduct)){
                                foreach ($orderDetailsProduct as $Pkey => $Pvalue) {
                                    if($Pvalue->total_quantity != ''){
                                            $value->buyBV += (int)($Pvalue->qty)*(float)($Pvalue->bv_amout);
                                            $value->buyDP += (float)($Pvalue->dp_amount)*(int)($Pvalue->qty);
                                            $value->refundBV += ((int)($Pvalue->total_quantity)-(int)($Pvalue->qty))*(float)($Pvalue->bv_amout);
                                            $value->refundDP += ((int)($Pvalue->total_quantity)-(int)($Pvalue->qty))*(float)($Pvalue->dp_amount);
                                            $value->refundQty += ((int)($Pvalue->total_quantity)-(int)($Cvalue->qty));
                                            $value->totalQty += ((int)($Pvalue->total_quantity));
                                        }
                                    else{
                                            $value->buyBV += (float)($Pvalue->bv_amout)*(int)($Pvalue->qty);
                                            $value->buyDP += (float)($Pvalue->dp_amount)*(int)($Pvalue->qty);
                                            $value->refundBV = "N/A";
                                            $value->refundDP = "N/A";
                                    }
                                }
                            }
                            $value->sponser_name = $this->Common_model->getCustomMemSelectData($value->tracking_id)[0]->full_name;
                            $value->new_invoice   = empty($invoiceDetails) ? '' : $invoiceDetails[0]->new_invoice;
                            $value->franchise_code = $this->Common_model->getCustomFranchiseDetails($value->franchise_id)[0]->franchise_code;
                            $value->refund_date   = empty($invoiceDetails[0]->refunded_date) ? '' : date('d-m-Y h:i:s a', strtotime($invoiceDetails[0]->refunded_date));
                            return $value;
                        }, $invoiceList);
                    }
                    $value->invoice_details = $invoiceList;
                }
                
            }
            else{
                foreach ($myDirects as $key => $value) {
                    $invoiceList = $this->Product_model->getMemberAllCompletedOrder($value->user_id, 'current');
                    if (!empty($invoiceList)) {
                        # code...
                        $invoiceList = array_map(function ($value) {
                            $invoiceDetails = $this->Product_model->getInvoiceDetailsByOrderId($value->id);
                            $orderDetailsPackage = $this->Product_model->getOrderDetailsByOrderId($value->id,'1');
                            $orderDetailsProduct = $this->Product_model->getOrderDetailsByOrderId($value->id,'0');
                            $value->buyBV = 0;
                            $value->buyDP = 0;
                            $value->refundBV = 0;
                            $value->refundDP = 0;
                            $value->refundQty = 0;
                            $value->totalQty = 0;
                            if(!empty($orderDetailsPackage)){
                                foreach ($orderDetailsPackage as $cKey => $Cvalue) {
                                    # code...
                                    
                                    if($Cvalue->total_quantity != ''){
                                        
                                        $value->buyBV += (int)($Cvalue->qty)*(float)($Cvalue->bv_amout);
                                        $value->buyDP += (int)($Cvalue->qty)*(float)($Cvalue->dp_amount);
                                        $value->refundBV += ((int)($Cvalue->total_quantity)-(int)($Cvalue->qty))*(float)($Cvalue->bv_amout);
                                        $value->refundDP += ((int)($Cvalue->total_quantity)-(int)($Cvalue->qty))*(float)($Cvalue->dp_amount);
                                        $value->refundQty += ((int)($Cvalue->total_quantity)-(int)($Cvalue->qty));
                                        $value->totalQty += ((int)($Cvalue->total_quantity));
                                    }
                                    else{
                                        $value->buyBV += (float)($Cvalue->bv_amout)*(int)($Cvalue->qty);
                                        $value->buyDP += (float)($Cvalue->dp_amount)*(int)($Cvalue->qty);
                                        $value->refundBV = "N/A";
                                        $value->refundDP = "N/A";
                                        $value->refundQty = "N/A";
                                    }
                                    
                                }
                    
                            }
                            if(!empty($orderDetailsProduct)){
                                
                                foreach ($orderDetailsProduct as $Pkey => $Pvalue) {
                                    if($Pvalue->total_quantity != ''){
                                            $value->buyBV += (int)($Pvalue->qty)*(float)($Pvalue->bv_amout);
                                            $value->buyDP += (float)($Pvalue->dp_amount)*(int)($Pvalue->qty);
                                            $value->refundQty += ((int)($Pvalue->total_quantity)-(int)($Pvalue->qty));
                                            $value->refundBV += ((int)($Pvalue->total_quantity)-(int)($Pvalue->qty))*(float)($Pvalue->bv_amout);
                                            $value->refundDP += ((int)($Pvalue->total_quantity)-(int)($Pvalue->qty))*(float)($Pvalue->dp_amount);
                                            $value->totalQty += ((int)($Pvalue->total_quantity));
                                        }
                                    else{
                                            $value->buyBV += (float)($Pvalue->bv_amout)*(int)($Pvalue->qty);
                                            $value->buyDP += (float)($Pvalue->dp_amount)*(int)($Pvalue->qty);
                                            $value->refundBV = "N/A";
                                            $value->refundDP = "N/A";
                                            $value->refundQty = "N/A";
                                            $value->totalQty = "N/A";
                                    }
                                }
                            }
                            $value->sponser_name = $this->Common_model->getCustomMemSelectData($value->tracking_id)[0]->full_name;
                            $value->new_invoice   = empty($invoiceDetails) ? '' : $invoiceDetails[0]->new_invoice;
                            $value->franchise_code = $this->Common_model->getCustomFranchiseDetails($value->franchise_id)[0]->franchise_code;
                            $value->refund_date   = empty($invoiceDetails[0]->refunded_date) ? '' : date('d-m-Y h:i:s a', strtotime($invoiceDetails[0]->refunded_date));
                            $value->billing_date   =  date('d-m-Y h:i:s a', strtotime($invoiceDetails[0]->created_date));
                            return $value;
                        }, $invoiceList);
                    }
                    $value->invoice_details = $invoiceList;
                }
            }
            
            
            
            $data = [
                'currentPage'         => '/wp-dashboard/invoice/team-invoice',
                'pageName'            => 'Team invoice',
                'invoiceList'         =>  $myDirects
            ];
            
            return $this->adminView('team-invoice', 'Products/Invoice/', $data);
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }
    /**************************************************** PRIVATE REQUEST ************************************************/
    private function getAutoGeneratedOrderId($digit = 10)
    {
        $orderId = 'ORDER' . date('Y') . $this->getRandomDigit($digit);
        $isValidOrderId = $this->Product_model->getOrderByOrderId($orderId);
        if (!empty($isValidOrderId)) {
            # code...
            return $this->getAutoGeneratedOrderId();
        }
        return $orderId;
    }

    private function getAutoGeneratedTransationId()
    {
        $TransationId = 'TRANS' . date('Y') . $this->getRandomDigit(4);
        $isValidTransationId = $this->Product_model->getFranchiseRequest($TransationId);
        if (!empty($isValidTransationId)) {
            # code...
            return $this->getAutoGeneratedTransationId();
        }
        return $TransationId;
    }

    private function getMemberTransationID()
    {
        $TransationId = 'TRANS' . date('Y') . $this->getRandomDigit(6);
        $isValidTransationId = $this->Product_model->getOrderMemByTransationId($TransationId);
        if (($isValidTransationId) > 0) {
            # code...
            return $this->getMemberTransationID();
        }
        return $TransationId;
    }

    private function generateClubRecords()
    {
    }

    private function generatePerformanceIncomeRecordsBySponserClub($memberObj, $orderObject, $currentLevel = '2' | '3', $totalOrderBVThisMonth = 0)
    {
        // $firstDayOfMonth = date('Y-m-01');
        // $lastDayOfMonth = date('Y-m-t');
        $getCurrentMonthSponserClubIncome = $this->Common_model->getCurrentMonthSponserClubIncomeById($orderObject[0]->mem_id);
        $extraBonous = $this->Common_model->getExtraBonous($orderObject[0]->mem_id);
        $extraBonousAmount = !empty($extraBonous) ? $extraBonous[0]->added_bv : 0.00;
        $isBVCountAgreed = ($orderObject[0]->total_bv  + $memberObj->total_bv + $extraBonousAmount) >= 25 ? true : false;
        if (empty($getCurrentMonthSponserClubIncome) && $isBVCountAgreed) {
            $myDirects = $memberObj->tracking_id === $memberObj->members_id ? $this->Report_model->getDirectUser($memberObj->tracking_id) : $this->Report_model->getDirectUser($memberObj->members_id, true);
            $myDirects = array_values(array_filter($myDirects, function ($value) {
                $orderDetails = $this->Common_model->getMemberOrderByMemIdCurrentMonth($value->user_id);
                $extraBonous = $this->Common_model->getExtraBonous($value->user_id);
                $extraBonousAmount = !empty($extraBonous) ? $extraBonous[0]->added_bv : 0.00;
                $thisMonth = array_reduce($orderDetails, function ($carry, $item) {
                    return $carry + $item->total_bv;
                }, 0.00);
                if (($thisMonth + $extraBonousAmount)  >= 25) {
                    # code...
                    return $value;
                }
            }));
            if (count($myDirects) >= 3) {
                # code...
                $insertData  = [
                    'user_id'    => $orderObject[0]->mem_id,
                    'current_level'   => $memberObj->current_level,
                    'created_date' => date('Y-m-d H:i:s')
                ];
                $this->Common_model->insertSponserClubDetails($insertData);
            }
        }
    }

    private function generatedPerforManceIncomeRecordsByLeaderShipClub($memberObj, $currentOrderBV = 0) {
        $orderDetails = $this->Common_model->getMemberOrderByMemIdCurrentMonth($memberObj->user_id);
        $thisMonth = 0.00;
        $extraBonous = $this->Common_model->getExtraBonous($memberObj->user_id);
        $extraBonousAmount = !empty($extraBonous) ? $extraBonous[0]->added_bv : 0.00;
        $thisMonth = array_reduce($orderDetails, function ($carry, $item) {
            return $carry + $item->total_bv;
        }, 0.00);
        $totalBv = $thisMonth + $currentOrderBV + $extraBonousAmount;
        $insertData = [
            'user_id' => $memberObj->user_id,
            'current_level' => $memberObj->current_level,
            'created_date' => date('Y-m-d H:i:s'),
            'club_promot_type' => '1'
        ];
        $getCurrentMonthLeaderShipIncome = $this->Common_model->getCurrentMonthLeaderShipIncomeById($memberObj->user_id);
        if ($memberObj->total_bv >= 25) {
            # code...
            if($memberObj->current_level == '4') {
                $insertData['club_promot_type'] = '1';
                
                // echo "<pre>";
                // print_r ($getCurrentMonthLeaderShipIncome);
                // echo ($totalBv . ">=". $this->leaderShipClubCTO('4')->bv_required);
                // echo "</pre>";
                // exit;
                if (empty($getCurrentMonthLeaderShipIncome) && ($totalBv >= $this->leaderShipClubCTO('4')->bv_required) ){
                    $this->Common_model->insertLeaderShipClubDetails($insertData);
                }
            } elseif($memberObj->current_level == '5') {
                $insertData['club_promot_type'] = '2';
                if (empty($getCurrentMonthLeaderShipIncome) && ($totalBv >= $this->leaderShipClubCTO('5')->bv_required) ){
                    $this->Common_model->insertLeaderShipClubDetails($insertData);
                }
            }elseif($memberObj->current_level == '6') {
                $insertData['club_promot_type'] = '3';
                if (empty($getCurrentMonthLeaderShipIncome) && ($totalBv >= $this->leaderShipClubCTO('6')->bv_required) ){
                     $this->Common_model->insertLeaderShipClubDetails($insertData);
                }
            } elseif ($memberObj->current_level == '7') {
                $insertData['club_promot_type'] = '4';
                if (empty($getCurrentMonthLeaderShipIncome) && ($totalBv >= $this->leaderShipClubCTO('7')->bv_required) ){
                     $this->Common_model->insertLeaderShipClubDetails($insertData);
                }
            } elseif ($memberObj->current_level == '8') {
                $insertData['club_promot_type'] = '5';
                if (empty($getCurrentMonthLeaderShipIncome) && ($totalBv >= $this->leaderShipClubCTO('8')->bv_required) ){
                    $this->Common_model->insertLeaderShipClubDetails($insertData);
                }
            } elseif ($memberObj->current_level == '9') {
                $insertData['club_promot_type'] = '3';
                if (empty($getCurrentMonthLeaderShipIncome) && ($totalBv >= $this->leaderShipClubCTO('9')->bv_required) ){
                    $this->Common_model->insertLeaderShipClubDetails($insertData);
                }
            } elseif ($memberObj->current_level == '10') {
                $insertData['club_promot_type'] = '4';
                if (empty($getCurrentMonthLeaderShipIncome) && ($totalBv >= $this->leaderShipClubCTO('10')->bv_required)) {
                    $this->Common_model->insertLeaderShipClubDetails($insertData);
                }
            } elseif ($memberObj->current_level == '11') {
                $insertData['club_promot_type'] = '5';
                if (empty($getCurrentMonthLeaderShipIncome) && ($totalBv >= $this->leaderShipClubCTO('11')->bv_required)) {
                    $this->Common_model->insertLeaderShipClubDetails($insertData);
                }
            }
        }
    }


    private function generatedPerforManceIncomeRecordsByStarShipClub($memberObj, $currentOrderBV = 0)
    {
        $orderDetails = $this->Common_model->getMemberOrderByMemIdCurrentMonth($memberObj->user_id);
        $thisMonth = 0.00;
        $thisMonth = array_reduce($orderDetails, function ($carry, $item) {
            return $carry + $item->total_bv;
        }, 0.00);
        $extraBonous = $this->Common_model->getExtraBonous($memberObj->user_id);
        $extraBonousAmount = !empty($extraBonous) ? $extraBonous[0]->added_bv : 0.00;
        $totalBv = $thisMonth + $currentOrderBV + $extraBonousAmount;
        $insertData = [
            'user_id' => $memberObj->user_id,
            'current_level' => $memberObj->current_level,
            'created_date' => date('Y-m-d H:i:s'),
            'club_promot_type' => '1'
        ];
        $getCurrentMonthLeaderShipIncome = $this->Common_model->getCurrentMonthStarShipIncomeById($memberObj->user_id);
        if ($memberObj->total_bv >= 25) {
            if ($memberObj->current_level == '9') {
                $insertData['club_promot_type'] = '1';
                if (empty($getCurrentMonthLeaderShipIncome) && ($totalBv >= $this->starClubCTO('9')->bv_required)) {
                    $this->Common_model->inserStarShipClubDetails($insertData);
                }
            } elseif ($memberObj->current_level == '10') {
                $insertData['club_promot_type'] = '2';
                if (empty($getCurrentMonthLeaderShipIncome) && ($totalBv >= $this->starClubCTO('10')->bv_required)) {
                    $this->Common_model->inserStarShipClubDetails($insertData);
                }
            } elseif ($memberObj->current_level == '11') {
                $insertData['club_promot_type'] = '3';
                if (empty($getCurrentMonthLeaderShipIncome) && ($totalBv >= $this->starClubCTO('11')->bv_required)) {
                    $this->Common_model->inserStarShipClubDetails($insertData);
                }
            }
        }
    }

    private function generatedPerforManceIncomeRecordsByDyanmicClub($memberObj, $currentOrderBV = 0)
    {
        $orderDetails = $this->Common_model->getMemberOrderByMemIdCurrentMonth($memberObj->user_id);
        $thisMonth = 0.00;
        $thisMonth = array_reduce($orderDetails, function ($carry, $item) {
            return $carry + $item->total_bv;
        }, 0.00);
        $extraBonous = $this->Common_model->getExtraBonous($memberObj->user_id);
        $extraBonousAmount = !empty($extraBonous) ? $extraBonous[0]->added_bv : 0.00;
        $totalBv = $thisMonth + $currentOrderBV + $extraBonousAmount;
        $insertData = [
            'user_id' => $memberObj->user_id,
            'current_level' => $memberObj->current_level,
            'created_date' => date('Y-m-d H:i:s'),
            'club_promot_type' => '1'
        ];
        $getCurrentMonthLeaderShipIncome = $this->Common_model->getCurrentMonthDynamicIncomeById($memberObj->user_id);
        if ($memberObj->total_bv >= 25) {
            if ($memberObj->current_level == '7') {
                $insertData['club_promot_type'] = '1';
                if (empty($getCurrentMonthLeaderShipIncome) && ($totalBv >= $this->dynamicCludCTO('7')->bv_required)) {
                    $this->Common_model->insertDynamicClubDetails($insertData);
                }
            } elseif ($memberObj->current_level == '8') {
                $insertData['club_promot_type'] = '2';
                if (empty($getCurrentMonthLeaderShipIncome) && ($totalBv >= $this->dynamicCludCTO('8')->bv_required)) {
                    $this->Common_model->insertDynamicClubDetails($insertData);
                }
            } elseif ($memberObj->current_level == '10') {
                $insertData['club_promot_type'] = '1';
                if (empty($getCurrentMonthLeaderShipIncome) && ($totalBv >= $this->dynamicCludCTO('10')->bv_required)) {
                    $this->Common_model->insertDynamicClubDetails($insertData);
                }
            } elseif ($memberObj->current_level == '11') {
                $insertData['club_promot_type'] = '2';
                if (empty($getCurrentMonthLeaderShipIncome) && ($totalBv >= $this->dynamicCludCTO('11')->bv_required)) {
                    $this->Common_model->insertDynamicClubDetails($insertData);
                }
            }
        }
    }

    private function loadMyDownLinesTotal($memId, $trackingId)
    {
        $myDirects = $trackingId === $memId ? $this->Report_model->getDirectUser($trackingId) : $this->Report_model->getDirectUser($memId, true);
        $allMembers = $this->Report_model->getAllNonDirectMem($trackingId);
        $myDownlineBv = 0;
        foreach ($allMembers as $key => $value) {
            # code...
            foreach ($myDirects as $cKey => $cValue) {
                # code...
                if ($value->tracking_id === $cValue->members_id) {
                    # code...
                    array_push($myDirects, $value);
                }
            }
        }


        foreach ($myDirects as $cKey => $cValue) {

            $orderDetails = $this->Common_model->getMemberOrderByMemIdCurrentMonth($cValue->user_id);
            $totalBV = 0.00;
            $totalBV = array_reduce($orderDetails, function ($carry, $item) {
                return $carry + $item->total_bv;
            }, 0.00);
            $myDownlineBv += $totalBV;
        }
        return $myDownlineBv;
    }

    private function setPerformanceBonousIncome($memberObj, $orderDetails) {
        
        $oldUplineMem1 = array_map(function($value) {
            if(!empty($value)){
                $value->temp_level = $value->current_level;
                if ($value->current_level == '6') {
                    # code...
                    $value->temp_level = '9';
                } elseif ($value->current_level == '7') {
                    # code...
                    $value->temp_level = '10';
                } elseif ($value->current_level == '8') {
                    # code...
                    $value->temp_level = '11';
                }
                return $value;
            }
            
            
        },$this->getAllUpLineMembers($memberObj->user_id));
        $uplineMember = [];
        $oldUplineMem = [];
        
        $a = 0;
        foreach($oldUplineMem1 as $value){
            if(!empty($value)){
                $oldUplineMem[$a] = $value;
                $a++;
            }
        }
        
        
        for ($i = (count($oldUplineMem) - 1); $i >= 0; $i--) {
            # code...
            $value = $oldUplineMem[$i];
            
            
                if ($i == 0) {
                    # code..
                    if (count($uplineMember) == 0
                    ) {
                        # code...
                        array_push($uplineMember, $value);
                    } else {
                        $isPush = true;
                        foreach ($uplineMember as $key => $upValue) {
                            # code...
                            if ($value->temp_level <= $upValue->temp_level) {
                                # code...
                                $isPush = false;
                            }
                            // echo "$value->current_level  " . " => $upValue->current_level <br>";
                        }
                        if ($isPush) {
                            # code...
                            // echo "IS-PUSH => $value->current_level <br>";
                            array_push($uplineMember, $value);
                        }  
                    }
                } elseif (($value->temp_level < $oldUplineMem[$i-1]->temp_level)) {
                    # code...
                    
                    if (count($uplineMember) == 0) {
                        # code...
                        array_push($uplineMember, $value);
                    } else {
                      $isPush = true;
                      foreach ($uplineMember as $key => $upValue) {
                          # code...
                          if ($value->temp_level <= $upValue->temp_level) {
                              # code...
                              $isPush = false;
                          }
                            // echo "$value->current_level  " . " => $upValue->current_level <br>";
                      }    
                      if ($isPush) {
                            # code...
                            // echo "IS-PUSH => $value->current_level <br>";
                            array_push($uplineMember, $value);
                      }       
                    }
                } else {
                    if (!$this->currentMemberLevelSearch($value->temp_level, $uplineMember)) {
                        array_push($uplineMember, $value);
                        // echo "else $value->current_level <br>";
                    }
    
                }
                if ($value->current_level == '8' || $value->current_level == '11') {
                    # code...
                    break;
                }
            
        }
        $mapOnlyCurrLevel = array_map(function($value) { return $value->current_level;}, $uplineMember);
        // $memberObj->current_level = '2';
        // echo $memberObj->user_id;
        // $orderDetails->total_bv = 20;
        // $memberObj->current_level = '';
        // echo "<pre>";
        // print_r ($uplineMember);
        // echo "</pre>";
        // echo "<br> ====================================== <br>";
        // echo "<pre>";
        // print_r ($memberObj);
        // echo "</pre>";
        // echo "<br> ====================================== <br>";
        // echo "<pre>";
        // print_r ($orderDetails);
        // echo "</pre>";
        $calculatedAmount = $this->calculateCurrentBVPrice($orderDetails->total_bv) * (float)$this->getCurrentBonousStructuralPercentage($memberObj->current_level);
        $adminCharge = 0;
        $tdsAmount = 0;
        if($this->getMemberIncomeTotalIncomeReports($memberObj->user_id) > 5000) {
            $tdsAmount = $calculatedAmount * $this->getTDSCharge();
            $adminCharge = $calculatedAmount * $this->getAdminCharge();
        }
        $insertData = [
            'user_id' => $memberObj->user_id,
            'leader_type' => $memberObj->current_level,
            'bv_requested' => $orderDetails->total_bv,
            'amount' => $calculatedAmount,
            'tds' => $tdsAmount,
            'admin_charge' => $adminCharge,
            'total_amount' => $calculatedAmount - $adminCharge - $tdsAmount,
            'comments' => "The income genereated for ".$memberObj->members_id. " line memebers",
            'created_date' => date('Y-m-d')
        ];
        if($memberObj->current_level !== '1' && ($memberObj->current_level != '8' || $memberObj->current_level == '11')) {
            $this->Common_model->insertPerformanceBonousRecords($insertData);
        }
        
        // echo "<pre>";
        // print_r ($oldUplineMem);
        // echo "<br> =================================== </br>";
        // print_r ($uplineMember);
        // echo "</pre>";
        // echo "<br> =================================== </br>";
        switch ($memberObj->current_level) {
            case '2':
                // $sdCount = $this->countArrayKey($mapOnlyCurrLevel, '8');
                // $starsdCount = $this->countArrayKey($mapOnlyCurrLevel, '11');
                // $bCount = $this->countArrayKey($mapOnlyCurrLevel, '2');
                // $isFoundBL = false;
                $dataArr = array_values(array_filter($uplineMember, function($value) { if($value->current_level > '2') {return $value;}}));
                $dCount = $this->countArrayKey($mapOnlyCurrLevel, '7');
                $stardCount = $this->countArrayKey($mapOnlyCurrLevel, '10');
                $pCount = $this->countArrayKey($mapOnlyCurrLevel, '6');
                $starPCount = $this->countArrayKey($mapOnlyCurrLevel, '9');
                $gCount = $this->countArrayKey($mapOnlyCurrLevel, '5');
                $sCount = $this->countArrayKey($mapOnlyCurrLevel, '4');
                $lCount = $this->countArrayKey($mapOnlyCurrLevel, '3');
                $numItems = count($dataArr);
                $i = 0;
                $isFoundLL = false;
                $isFoundSL = false;
                $isFoundGL = false;
                $isFoundPL = false;
                $isFoundDL = false;
                $isFoundSDL = false;
                $dlCalc = 0.00;
                // echo " $lCount - $sCount - $gCount - $pCount - $dCount - $stardCount - $starPCount <br>";

                for ($i = 0; $i < $numItems; $i++) {
                    # code...
                    $value = $dataArr[$i];

                    // Loyal Leader
                    if ($dataArr[$i]->current_level == '3' && !$isFoundLL) {
                        # code...
                        $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('3') - (float)$this->getCurrentBonousStructuralPercentage('2');
                        $isFoundLL = true;
                        // echo $this->calculateCurrentBVPrice($orderDetails->total_bv) . "=>" . $dlCalc . " => " . $value->current_level . "<br>";

                        $calculatedAmount = $this->calculateCurrentBVPrice($orderDetails->total_bv) * $dlCalc;
                        $adminCharge = 0;
                        $tdsAmount = 0;
                        if ($this->getMemberIncomeTotalIncomeReports($memberObj->user_id) > 5000) {
                            $tdsAmount = $calculatedAmount * $this->getTDSCharge();
                            $adminCharge = $calculatedAmount * $this->getAdminCharge();
                        }
                        $insertData = [
                            'user_id' => $value->user_id,
                            'promoted_userid' => $memberObj->user_id,
                            'leader_type' => $value->current_level,
                            'bv_requested' => $orderDetails->total_bv,
                            'amount' => $calculatedAmount,
                            'tds' => $tdsAmount,
                            'admin_charge' => $adminCharge,
                            'total_amount' => $calculatedAmount - $adminCharge - $tdsAmount,
                            'created_date' => date('Y-m-d'),
                            'comments' => "The income genereated for ".$memberObj->members_id. " line memebers",
                        ];
                        $this->Common_model->insertPerformanceBonousRecords($insertData);
                        continue;
                    }

                    // Silver Leader
                    if ($dataArr[$i]->current_level == '4' && !$isFoundSL
                    ) {
                        # code...
                        $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('4') - (float)$this->getCurrentBonousStructuralPercentage('2');
                        if($lCount >= 1){
                            $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('4') - (float)$this->getCurrentBonousStructuralPercentage('3');
                        }
                        $isFoundSL = true;
                        // echo $this->calculateCurrentBVPrice($orderDetails->total_bv) . "=>" . $dlCalc . " => " . $value->current_level . "<br>";

                        $calculatedAmount = $this->calculateCurrentBVPrice($orderDetails->total_bv) * $dlCalc;
                        $adminCharge = 0;
                        $tdsAmount = 0;
                        if ($this->getMemberIncomeTotalIncomeReports($memberObj->user_id) > 5000) {
                            $tdsAmount = $calculatedAmount * $this->getTDSCharge();
                            $adminCharge = $calculatedAmount * $this->getAdminCharge();
                        }
                        $insertData = [
                            'user_id' => $value->user_id,
                            'promoted_userid' => $memberObj->user_id,
                            'leader_type' => $value->current_level,
                            'bv_requested' => $orderDetails->total_bv,
                            'amount' => $calculatedAmount,
                            'tds' => $tdsAmount,
                            'admin_charge' => $adminCharge,
                            'total_amount' => $calculatedAmount - $adminCharge - $tdsAmount,
                            'created_date' => date('Y-m-d'),
                            'comments' => "The income genereated for " . $memberObj->members_id . " line memebers",
                        ];
                        $this->Common_model->insertPerformanceBonousRecords($insertData);
                        continue;
                    }
                    // Golden Leader
                    if (
                        $dataArr[$i]->current_level == '5' && !$isFoundGL
                    ) {
                        # code...
                        $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('5') - (float)$this->getCurrentBonousStructuralPercentage('2');
                        if ($lCount >= 1) {
                            $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('5') - (float)$this->getCurrentBonousStructuralPercentage('3');
                        }
                        if ($sCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('5') -  (float)$this->getCurrentBonousStructuralPercentage('4'));
                        }
                        $isFoundGL = true;
                        // echo $this->calculateCurrentBVPrice($orderDetails->total_bv) . "=>" . $dlCalc . " => " . $value->current_level . "<br>";

                        $calculatedAmount = $this->calculateCurrentBVPrice($orderDetails->total_bv) * $dlCalc;
                        $adminCharge = 0;
                        $tdsAmount = 0;
                        if ($this->getMemberIncomeTotalIncomeReports($memberObj->user_id) > 5000) {
                            $tdsAmount = $calculatedAmount * $this->getTDSCharge();
                            $adminCharge = $calculatedAmount * $this->getAdminCharge();
                        }
                        $insertData = [
                            'user_id' => $value->user_id,
                            'promoted_userid' => $memberObj->user_id,
                            'leader_type' => $value->current_level,
                            'bv_requested' => $orderDetails->total_bv,
                            'amount' => $calculatedAmount,
                            'tds' => $tdsAmount,
                            'admin_charge' => $adminCharge,
                            'total_amount' => $calculatedAmount - $adminCharge - $tdsAmount,
                            'created_date' => date('Y-m-d'),
                            'comments' => "The income genereated for " . $memberObj->members_id . " line memebers",
                        ];
                        $this->Common_model->insertPerformanceBonousRecords($insertData);
                        continue;
                    }

                    // Platinum Leader
                    if (
                        ($dataArr[$i]->current_level == '6' || $dataArr[$i]->current_level == '9') && !$isFoundPL
                    ) {
                        # code...
                        $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('6') - (float)$this->getCurrentBonousStructuralPercentage('2');
                        if ($lCount >= 1) {
                            $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('6') - (float)$this->getCurrentBonousStructuralPercentage('3');
                        }
                        if ($sCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('6') -  (float)$this->getCurrentBonousStructuralPercentage('4'));
                        }
                        if ($gCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('6') -  (float)$this->getCurrentBonousStructuralPercentage('5'));
                        }
                        $isFoundPL = true;
                        // echo $this->calculateCurrentBVPrice($orderDetails->total_bv) . "=>" . $dlCalc . " => " . $value->current_level . "<br>";

                        $calculatedAmount = $this->calculateCurrentBVPrice($orderDetails->total_bv) * $dlCalc;
                        $adminCharge = 0;
                        $tdsAmount = 0;
                        if ($this->getMemberIncomeTotalIncomeReports($memberObj->user_id) > 5000) {
                            $tdsAmount = $calculatedAmount * $this->getTDSCharge();
                            $adminCharge = $calculatedAmount * $this->getAdminCharge();
                        }
                        $insertData = [
                            'user_id' => $value->user_id,
                            'promoted_userid' => $memberObj->user_id,
                            'leader_type' => $value->current_level,
                            'bv_requested' => $orderDetails->total_bv,
                            'amount' => $calculatedAmount,
                            'tds' => $tdsAmount,
                            'admin_charge' => $adminCharge,
                            'total_amount' => $calculatedAmount - $adminCharge- $tdsAmount,
                            'created_date' => date('Y-m-d'),
                            'comments' => "The income genereated for " . $memberObj->members_id . " line memebers",
                        ];
                        $this->Common_model->insertPerformanceBonousRecords($insertData);
                        continue;
                    }

                    // Dynamic Leader
                    if (
                        ($dataArr[$i]->current_level == '7' || $dataArr[$i]->current_level == '10') && !$isFoundDL
                    ) {
                        # code...
                        $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('7') - (float)$this->getCurrentBonousStructuralPercentage('2');
                        if ($lCount >= 1) {
                            $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('7') - (float)$this->getCurrentBonousStructuralPercentage('3');
                        }
                        if ($sCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('7') -  (float)$this->getCurrentBonousStructuralPercentage('4'));
                        }
                        if ($gCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('7') -  (float)$this->getCurrentBonousStructuralPercentage('5'));
                        }
                        if ($pCount >= 1 || $starPCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('7') -  (float)$this->getCurrentBonousStructuralPercentage('6'));
                        }
                        $isFoundDL = true;
                        // echo $this->calculateCurrentBVPrice($orderDetails->total_bv) . "=>" . $dlCalc . " => " . $value->current_level . "<br>";

                        $calculatedAmount = $this->calculateCurrentBVPrice($orderDetails->total_bv) * $dlCalc;
                        $adminCharge = 0;
                        $tdsAmount = 0;
                        if ($this->getMemberIncomeTotalIncomeReports($memberObj->user_id) > 5000) {
                            $tdsAmount = $calculatedAmount * $this->getTDSCharge();
                            $adminCharge = $calculatedAmount * $this->getAdminCharge();
                        }
                        $insertData = [
                            'user_id' => $value->user_id,
                            'promoted_userid' => $memberObj->user_id,
                            'leader_type' => $value->current_level,
                            'bv_requested' => $orderDetails->total_bv,
                            'amount' => $calculatedAmount,
                            'tds' => $tdsAmount,
                            'admin_charge' => $adminCharge,
                            'total_amount' => $calculatedAmount - $adminCharge - $tdsAmount,
                            'created_date' => date('Y-m-d'),
                            'comments' => "The income genereated for " . $memberObj->members_id . " line memebers",
                        ];
                        $this->Common_model->insertPerformanceBonousRecords($insertData);
                        continue;
                    }

                    //  Super Dynamic Leader Counting 
                    if (
                        ($dataArr[$i]->current_level == '8' || $dataArr[$i]->current_level == '11') && !$isFoundSDL
                    ) {
                        $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('8') - (float)$this->getCurrentBonousStructuralPercentage('2');
                        if ($lCount >= 1) {
                            $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('8') - (float)$this->getCurrentBonousStructuralPercentage('3');
                        }
                        if ($sCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('8') -  (float)$this->getCurrentBonousStructuralPercentage('4'));
                        }
                        if ($gCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('8') -  (float)$this->getCurrentBonousStructuralPercentage('5'));
                        }
                        if ($pCount >= 1 || $starPCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('8') -  (float)$this->getCurrentBonousStructuralPercentage('6'));
                        }
                        if ($dCount >= 1 || $stardCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('8') -  (float)$this->getCurrentBonousStructuralPercentage('7'));
                        }
                // echo
                //         $this->calculateCurrentBVPrice($orderDetails->total_bv) . "=>" . $dlCalc . " => " . $value->current_level . "<br>";

                        $isFoundSDL = true;
                        $calculatedAmount = $this->calculateCurrentBVPrice($orderDetails->total_bv) * $dlCalc;
                        $adminCharge = 0;
                        $tdsAmount = 0;
                        if ($this->getMemberIncomeTotalIncomeReports($memberObj->user_id) > 5000) {
                            $tdsAmount = $calculatedAmount * $this->getTDSCharge();
                            $adminCharge = $calculatedAmount * $this->getAdminCharge();
                        }
                        $insertData = [
                            'user_id' => $value->user_id,
                            'promoted_userid' => $memberObj->user_id,
                            'leader_type' => $value->current_level,
                            'bv_requested' => $orderDetails->total_bv,
                            'amount' => $calculatedAmount,
                            'tds' => $tdsAmount,
                            'admin_charge' => $adminCharge,
                            'total_amount' => $calculatedAmount - $adminCharge - $tdsAmount,
                            'created_date' => date('Y-m-d'),
                            'comments' => "The income genereated for " . $memberObj->members_id . " line memebers",
                        ];
                        $this->Common_model->insertPerformanceBonousRecords($insertData);
                        continue;
                    }
                }
                break;
            case '3':
                $dataArr = array_values(array_filter($uplineMember, function ($value) {
                    if ($value->current_level > '3') {
                        return $value;
                    }
                }));
                $dCount = $this->countArrayKey($mapOnlyCurrLevel, '7');
                $stardCount = $this->countArrayKey($mapOnlyCurrLevel, '10');
                $pCount = $this->countArrayKey($mapOnlyCurrLevel, '6');
                $starPCount = $this->countArrayKey($mapOnlyCurrLevel, '9');
                $gCount = $this->countArrayKey($mapOnlyCurrLevel, '5');
                $sCount = $this->countArrayKey($mapOnlyCurrLevel, '4');
                $numItems = count($dataArr);
                $i = 0;
                $isFoundLL = false;
                $isFoundSL = false;
                $isFoundGL = false;
                $isFoundPL = false;
                $isFoundDL = false;
                $isFoundSDL = false;
                $dlCalc = 0.00;
               for ($i = 0; $i < $numItems; $i++) {
                    # code...
                    $value = $dataArr[$i];


                    // Silver Leader
                    if (
                        $dataArr[$i]->current_level == '4' && !$isFoundSL
                    ) {
                        # code...
                        $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('4') - (float)$this->getCurrentBonousStructuralPercentage('3');
                        $isFoundSL = true;

                        $calculatedAmount = $this->calculateCurrentBVPrice($orderDetails->total_bv) * $dlCalc;
                        $adminCharge = 0;
                        $tdsAmount = 0;
                        if ($this->getMemberIncomeTotalIncomeReports($memberObj->user_id) > 5000) {
                            $tdsAmount = $calculatedAmount * $this->getTDSCharge();
                            $adminCharge = $calculatedAmount * $this->getAdminCharge();
                        }
                        $insertData = [
                            'user_id' => $value->user_id,
                            'promoted_userid' => $memberObj->user_id,
                            'leader_type' => $value->current_level,
                            'bv_requested' => $orderDetails->total_bv,
                            'amount' => $calculatedAmount,
                            'tds' => $tdsAmount,
                            'admin_charge' => $adminCharge,
                            'total_amount' => $calculatedAmount - $adminCharge - $tdsAmount,
                            'created_date' => date('Y-m-d'),
                            'comments' => "The income genereated for " . $memberObj->members_id . " line memebers",
                        ];
                        $this->Common_model->insertPerformanceBonousRecords($insertData);
                        continue;
                    }
                    // Golden Leader
                    if (
                        $dataArr[$i]->current_level == '5' && !$isFoundGL
                    ) {
                        # code...
                        $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('5') - (float)$this->getCurrentBonousStructuralPercentage('3');
                        
                        if ($sCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('5') -  (float)$this->getCurrentBonousStructuralPercentage('4'));
                        }
                        $isFoundGL = true;
                        // echo $this->calculateCurrentBVPrice($orderDetails->total_bv) . "=>" . $dlCalc . " => " . $value->current_level . "<br>";
                        $calculatedAmount = $this->calculateCurrentBVPrice($orderDetails->total_bv) * $dlCalc;
                        $adminCharge = 0;
                        $tdsAmount = 0;
                        if ($this->getMemberIncomeTotalIncomeReports($memberObj->user_id) > 5000) {
                            $tdsAmount = $calculatedAmount * $this->getTDSCharge();
                            $adminCharge = $calculatedAmount * $this->getAdminCharge();
                        }
                        $insertData = [
                            'user_id' => $value->user_id,
                            'promoted_userid' => $memberObj->user_id,
                            'leader_type' => $value->current_level,
                            'bv_requested' => $orderDetails->total_bv,
                            'amount' => $calculatedAmount,
                            'tds' => $tdsAmount,
                            'admin_charge' => $adminCharge,
                            'total_amount' => $calculatedAmount - $adminCharge - $tdsAmount,
                            'created_date' => date('Y-m-d'),
                            'comments' => "The income genereated for " . $memberObj->members_id . " line memebers",
                        ];
                        $this->Common_model->insertPerformanceBonousRecords($insertData);
                        continue;
                    }

                    // Platinum Leader
                    if (
                        ($dataArr[$i]->current_level == '6' || $dataArr[$i]->current_level == '9') && !$isFoundPL
                    ) {
                        # code...
                        $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('6') - (float)$this->getCurrentBonousStructuralPercentage('3');
                        if ($sCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('6') -  (float)$this->getCurrentBonousStructuralPercentage('4'));
                        }
                        if ($gCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('6') -  (float)$this->getCurrentBonousStructuralPercentage('5'));
                        }
                        $isFoundPL = true;
                        // echo $this->calculateCurrentBVPrice($orderDetails->total_bv) . "=>" . $dlCalc . " => " . $value->current_level . "<br>";
                        $calculatedAmount = $this->calculateCurrentBVPrice($orderDetails->total_bv) * $dlCalc;
                        $adminCharge = 0;
                        $tdsAmount = 0;
                        if ($this->getMemberIncomeTotalIncomeReports($memberObj->user_id) > 5000) {
                            $tdsAmount = $calculatedAmount * $this->getTDSCharge();
                            $adminCharge = $calculatedAmount * $this->getAdminCharge();
                        }
                        $insertData = [
                            'user_id' => $value->user_id,
                            'promoted_userid' => $memberObj->user_id,
                            'leader_type' => $value->current_level,
                            'bv_requested' => $orderDetails->total_bv,
                            'amount' => $calculatedAmount,
                            'tds' => $tdsAmount,
                            'admin_charge' => $adminCharge,
                            'total_amount' => $calculatedAmount - $adminCharge - $tdsAmount,
                            'created_date' => date('Y-m-d'),
                            'comments' => "The income genereated for " . $memberObj->members_id . " line memebers",
                        ];
                        $this->Common_model->insertPerformanceBonousRecords($insertData);
                        continue;
                    }

                    // Dynamic Leader
                    if (
                        ($dataArr[$i]->current_level == '7' || $dataArr[$i]->current_level == '10') && !$isFoundDL
                    ) {
                        # code...
                        $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('7') - (float)$this->getCurrentBonousStructuralPercentage('3');
                        
                        if ($sCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('7') -  (float)$this->getCurrentBonousStructuralPercentage('4'));
                        }
                        if ($gCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('7') -  (float)$this->getCurrentBonousStructuralPercentage('5'));
                        }
                        if ($pCount >= 1 || $starPCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('7') -  (float)$this->getCurrentBonousStructuralPercentage('6'));
                        }
                        $isFoundDL = true;
                        // echo $this->calculateCurrentBVPrice($orderDetails->total_bv) . "=>" . $dlCalc . " => " . $value->current_level . "<br>";
                        $calculatedAmount = $this->calculateCurrentBVPrice($orderDetails->total_bv) * $dlCalc;
                        $adminCharge = 0;
                        $tdsAmount = 0;
                        if ($this->getMemberIncomeTotalIncomeReports($memberObj->user_id) > 5000) {
                            $tdsAmount = $calculatedAmount * $this->getTDSCharge();
                            $adminCharge = $calculatedAmount * $this->getAdminCharge();
                        }
                        $insertData = [
                            'user_id' => $value->user_id,
                            'promoted_userid' => $memberObj->user_id,
                            'leader_type' => $value->current_level,
                            'bv_requested' => $orderDetails->total_bv,
                            'amount' => $calculatedAmount,
                            'tds' => $tdsAmount,
                            'admin_charge' => $adminCharge,
                            'total_amount' => $calculatedAmount - $adminCharge - $tdsAmount,
                            'created_date' => date('Y-m-d'),
                            'comments' => "The income genereated for " . $memberObj->members_id . " line memebers",
                        ];
                        $this->Common_model->insertPerformanceBonousRecords($insertData);
                        continue;
                    }

                    //  Super Dynamic Leader Counting 
                    if (
                        ($dataArr[$i]->current_level == '8' || $dataArr[$i]->current_level == '11') && !$isFoundSDL
                    ) {
                        $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('8') - (float)$this->getCurrentBonousStructuralPercentage('3');
                        
                        if ($sCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('8') -  (float)$this->getCurrentBonousStructuralPercentage('4'));
                        }
                        if ($gCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('8') -  (float)$this->getCurrentBonousStructuralPercentage('5'));
                        }
                        if ($pCount >= 1 || $starPCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('8') -  (float)$this->getCurrentBonousStructuralPercentage('6'));
                        }
                        if ($dCount >= 1 || $stardCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('8') -  (float)$this->getCurrentBonousStructuralPercentage('7'));
                        }
                        // echo $this->calculateCurrentBVPrice($orderDetails->total_bv) . "=>" . $dlCalc . " => " . $value->current_level . "<br>";
                        $isFoundSDL = true;
                        $calculatedAmount = $this->calculateCurrentBVPrice($orderDetails->total_bv) * $dlCalc;
                        $adminCharge = 0;
                        $tdsAmount = 0;
                        if ($this->getMemberIncomeTotalIncomeReports($memberObj->user_id) > 5000) {
                            $tdsAmount = $calculatedAmount * $this->getTDSCharge();
                            $adminCharge = $calculatedAmount * $this->getAdminCharge();
                        }
                        $insertData = [
                            'user_id' => $value->user_id,
                            'promoted_userid' => $memberObj->user_id,
                            'leader_type' => $value->current_level,
                            'bv_requested' => $orderDetails->total_bv,
                            'amount' => $calculatedAmount,
                            'tds' => $tdsAmount,
                            'admin_charge' => $adminCharge,
                            'total_amount' => $calculatedAmount - $adminCharge - $tdsAmount,
                            'created_date' => date('Y-m-d'),
                            'comments' => "The income genereated for " . $memberObj->members_id . " line memebers",
                        ];
                        $this->Common_model->insertPerformanceBonousRecords($insertData);
                        continue;
                    }
                }
                break;
            case '4':
                $dataArr = array_values(array_filter($uplineMember, function($value) { if($value->current_level > '4') {return $value;}}));
                $dCount = $this->countArrayKey($mapOnlyCurrLevel, '7');
                $stardCount = $this->countArrayKey($mapOnlyCurrLevel, '10');
                $pCount = $this->countArrayKey($mapOnlyCurrLevel, '6');
                $starPCount = $this->countArrayKey($mapOnlyCurrLevel, '9');
                $gCount = $this->countArrayKey($mapOnlyCurrLevel, '5');
                $numItems = count($dataArr);
                $i = 0;
                $isFoundLL = false;
                $isFoundSL = false;
                $isFoundGL = false;
                $isFoundPL = false;
                $isFoundDL = false;
                $isFoundSDL = false;
                $dlCalc = 0.00;
               for ($i = 0; $i < $numItems; $i++) {
                    # code...
                    $value = $dataArr[$i];


                    // Golden Leader
                    if (
                        $dataArr[$i]->current_level == '5' && !$isFoundGL
                    ) {
                        # code...
                        $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('5') - (float)$this->getCurrentBonousStructuralPercentage('4');

                        $isFoundGL = true;

                        // echo $this->
                        // calculateCurrentBVPrice($orderDetails->total_bv) . "=>" . $dlCalc . " => " . $value->current_level . "<br>";
                        $calculatedAmount = $this->calculateCurrentBVPrice($orderDetails->total_bv) * $dlCalc;
                        $adminCharge = 0;
                        $tdsAmount = 0;
                        if ($this->getMemberIncomeTotalIncomeReports($memberObj->user_id) > 5000) {
                            $tdsAmount = $calculatedAmount * $this->getTDSCharge();
                            $adminCharge = $calculatedAmount * $this->getAdminCharge();
                        }
                        $insertData = [
                            'user_id' => $value->user_id,
                            'promoted_userid' => $memberObj->user_id,
                            'leader_type' => $value->current_level,
                            'bv_requested' => $orderDetails->total_bv,
                            'amount' => $calculatedAmount,
                            'tds' => $tdsAmount,
                            'admin_charge' => $adminCharge,
                            'total_amount' => $calculatedAmount - $adminCharge - $tdsAmount,
                            'created_date' => date('Y-m-d'),
                            'comments' => "The income genereated for " . $memberObj->members_id . " line memebers",
                        ];
                        $this->Common_model->insertPerformanceBonousRecords($insertData);
                        continue;
                    }

                    // Platinum Leader
                    if (
                        ($dataArr[$i]->current_level == '6' || $dataArr[$i]->current_level == '9') && !$isFoundPL
                    ) {
                        # code...
                        $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('6') - (float)$this->getCurrentBonousStructuralPercentage('4');
                        
                        if ($gCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('6') -  (float)$this->getCurrentBonousStructuralPercentage('5'));
                        }
                        $isFoundPL = true;
                        // echo $this->calculateCurrentBVPrice($orderDetails->total_bv) . "=>" . $dlCalc . " => " . $value->current_level . "<br>";

                        $calculatedAmount = $this->calculateCurrentBVPrice($orderDetails->total_bv) * $dlCalc;
                        $adminCharge = 0;
                        $tdsAmount = 0;
                        if ($this->getMemberIncomeTotalIncomeReports($memberObj->user_id) > 5000) {
                            $tdsAmount = $calculatedAmount * $this->getTDSCharge();
                            $adminCharge = $calculatedAmount * $this->getAdminCharge();
                        }
                        $insertData = [
                            'user_id' => $value->user_id,
                            'promoted_userid' => $memberObj->user_id,
                            'leader_type' => $value->current_level,
                            'bv_requested' => $orderDetails->total_bv,
                            'amount' => $calculatedAmount,
                            'tds' => $tdsAmount,
                            'admin_charge' => $adminCharge,
                            'total_amount' => $calculatedAmount - $adminCharge - $tdsAmount,
                            'created_date' => date('Y-m-d'),
                            'comments' => "The income genereated for " . $memberObj->members_id . " line memebers",
                        ];
                        $this->Common_model->insertPerformanceBonousRecords($insertData);
                        continue;
                    }

                    // Dynamic Leader
                    if (
                        ($dataArr[$i]->current_level == '7' || $dataArr[$i]->current_level == '10') && !$isFoundDL
                    ) {
                        # code...
                        $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('7') - (float)$this->getCurrentBonousStructuralPercentage('4');

                        if ($gCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('7') -  (float)$this->getCurrentBonousStructuralPercentage('5'));
                        }
                        if ($pCount >= 1 || $starPCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('7') -  (float)$this->getCurrentBonousStructuralPercentage('6'));
                        }
                        // echo $this->
                        // calculateCurrentBVPrice($orderDetails->total_bv) . "=>" . $dlCalc . " => " . $value->current_level . "<br>";
                        $isFoundDL = true;

                        $calculatedAmount = $this->calculateCurrentBVPrice($orderDetails->total_bv) * $dlCalc;
                        $adminCharge = 0;
                        $tdsAmount = 0;
                        if ($this->getMemberIncomeTotalIncomeReports($memberObj->user_id) > 5000) {
                            $tdsAmount = $calculatedAmount * $this->getTDSCharge();
                            $adminCharge = $calculatedAmount * $this->getAdminCharge();
                        }
                        $insertData = [
                            'user_id' => $value->user_id,
                            'promoted_userid' => $memberObj->user_id,
                            'leader_type' => $value->current_level,
                            'bv_requested' => $orderDetails->total_bv,
                            'amount' => $calculatedAmount,
                            'tds' => $tdsAmount,
                            'admin_charge' => $adminCharge,
                            'total_amount' => $calculatedAmount - $adminCharge - $tdsAmount,
                            'created_date' => date('Y-m-d'),
                            'comments' => "The income genereated for " . $memberObj->members_id . " line memebers",
                        ];
                        $this->Common_model->insertPerformanceBonousRecords($insertData);
                        continue;
                    }

                    //  Super Dynamic Leader Counting 
                    if (
                        ($dataArr[$i]->current_level == '8' || $dataArr[$i]->current_level == '11') && !$isFoundSDL
                    ) {
                        $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('8') - (float)$this->getCurrentBonousStructuralPercentage('4');

                        if ($gCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('8') -  (float)$this->getCurrentBonousStructuralPercentage('5'));
                        }
                        if ($pCount >= 1 || $starPCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('8') -  (float)$this->getCurrentBonousStructuralPercentage('6'));
                        }
                        if ($dCount >= 1 || $stardCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('8') -  (float)$this->getCurrentBonousStructuralPercentage('7'));
                        }
                        // echo $this->calculateCurrentBVPrice($orderDetails->total_bv) . "=>" . $dlCalc . " => " . $value->current_level . "<br>";

                        $isFoundSDL = true;
                        $calculatedAmount = $this->calculateCurrentBVPrice($orderDetails->total_bv) * $dlCalc;
                        $adminCharge = 0;
                        $tdsAmount = 0;
                        if ($this->getMemberIncomeTotalIncomeReports($memberObj->user_id) > 5000) {
                            $tdsAmount = $calculatedAmount * $this->getTDSCharge();
                            $adminCharge = $calculatedAmount * $this->getAdminCharge();
                        }
                        $insertData = [
                            'user_id' => $value->user_id,
                            'promoted_userid' => $memberObj->user_id,
                            'leader_type' => $value->current_level,
                            'bv_requested' => $orderDetails->total_bv,
                            'amount' => $calculatedAmount,
                            'tds' => $tdsAmount,
                            'admin_charge' => $adminCharge,
                            'total_amount' => $calculatedAmount - $adminCharge - $tdsAmount,
                            'created_date' => date('Y-m-d'),
                            'comments' => "The income genereated for " . $memberObj->members_id . " line memebers",
                        ];
                        $this->Common_model->insertPerformanceBonousRecords($insertData);
                        continue;
                    }
                }
                break;
            case '5':
                $dataArr = array_values(array_filter($uplineMember, function($value) { if($value->current_level > '5') {return $value;}}));
                $dCount = $this->countArrayKey($mapOnlyCurrLevel, '7');
                $stardCount = $this->countArrayKey($mapOnlyCurrLevel, '10');
                $pCount = $this->countArrayKey($mapOnlyCurrLevel, '6');
                $starPCount = $this->countArrayKey($mapOnlyCurrLevel, '9');
                $numItems = count($dataArr);
                $i = 0;
                $isFoundLL = false;
                $isFoundSL = false;
                $isFoundGL = false;
                $isFoundPL = false;
                $isFoundDL = false;
                $isFoundSDL = false;
                $dlCalc = 0.00;
               for ($i = 0; $i < $numItems; $i++) {
                    # code...
                    $value = $dataArr[$i];



                    // Platinum Leader
                    if (
                        ($dataArr[$i]->current_level == '6' || $dataArr[$i]->current_level == '9') && !$isFoundPL
                    ) {
                        # code...
                        $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('6') - (float)$this->getCurrentBonousStructuralPercentage('5');
                        // echo $this->calculateCurrentBVPrice($orderDetails->total_bv) . "=>" . $dlCalc . " => " . $value->current_level . "<br>";

                        $isFoundPL = true;

                        $calculatedAmount = $this->calculateCurrentBVPrice($orderDetails->total_bv) * $dlCalc;
                        $adminCharge = 0;
                        $tdsAmount = 0;
                        if ($this->getMemberIncomeTotalIncomeReports($memberObj->user_id) > 5000) {
                            $tdsAmount = $calculatedAmount * $this->getTDSCharge();
                            $adminCharge = $calculatedAmount * $this->getAdminCharge();
                        }
                        $insertData = [
                            'user_id' => $value->user_id,
                            'promoted_userid' => $memberObj->user_id,
                            'leader_type' => $value->current_level,
                            'bv_requested' => $orderDetails->total_bv,
                            'amount' => $calculatedAmount,
                            'tds' => $tdsAmount,
                            'admin_charge' => $adminCharge,
                            'total_amount' => $calculatedAmount - $adminCharge - $tdsAmount,
                            'created_date' => date('Y-m-d'),
                            'comments' => "The income genereated for " . $memberObj->members_id . " line memebers",
                        ];
                        $this->Common_model->insertPerformanceBonousRecords($insertData);
                        continue;
                    }

                    // Dynamic Leader
                    if (
                        ($dataArr[$i]->current_level == '7' || $dataArr[$i]->current_level == '10') && !$isFoundDL
                    ) {
                        # code...
                        $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('7') - (float)$this->getCurrentBonousStructuralPercentage('5');
                        // echo $this->calculateCurrentBVPrice($orderDetails->total_bv) . "=>" . $dlCalc . " => " . $value->current_level . "<br>";

                        if ($pCount >= 1 || $starPCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('7') -  (float)$this->getCurrentBonousStructuralPercentage('6'));
                        }
                        $isFoundDL = true;

                        $calculatedAmount = $this->calculateCurrentBVPrice($orderDetails->total_bv) * $dlCalc;
                        $adminCharge = 0;
                        $tdsAmount = 0;
                        if ($this->getMemberIncomeTotalIncomeReports($memberObj->user_id) > 5000) {
                            $tdsAmount = $calculatedAmount * $this->getTDSCharge();
                            $adminCharge = $calculatedAmount * $this->getAdminCharge();
                        }
                        $insertData = [
                            'user_id' => $value->user_id,
                            'promoted_userid' => $memberObj->user_id,
                            'leader_type' => $value->current_level,
                            'bv_requested' => $orderDetails->total_bv,
                            'amount' => $calculatedAmount,
                            'tds' => $tdsAmount,
                            'admin_charge' => $adminCharge,
                            'total_amount' => $calculatedAmount - $adminCharge - $tdsAmount,
                            'created_date' => date('Y-m-d'),
                            'comments' => "The income genereated for " . $memberObj->members_id . " line memebers",
                        ];
                        $this->Common_model->insertPerformanceBonousRecords($insertData);
                        continue;
                    }

                    //  Super Dynamic Leader Counting 
                    if (
                        ($dataArr[$i]->current_level == '8' || $dataArr[$i]->current_level == '11') && !$isFoundSDL
                    ) {
                        $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('8') - (float)$this->getCurrentBonousStructuralPercentage('5');
                        // echo $this->calculateCurrentBVPrice($orderDetails->total_bv) . "=>" . $dlCalc . " => " . $value->current_level . "<br>";

                        if ($pCount >= 1 || $starPCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('8') -  (float)$this->getCurrentBonousStructuralPercentage('6'));
                        }
                        if ($dCount >= 1 || $stardCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('8') -  (float)$this->getCurrentBonousStructuralPercentage('7'));
                        }
                        $isFoundSDL = true;
                        $calculatedAmount = $this->calculateCurrentBVPrice($orderDetails->total_bv) * $dlCalc;
                        $adminCharge = 0;
                        $tdsAmount = 0;
                        if ($this->getMemberIncomeTotalIncomeReports($memberObj->user_id) > 5000) {
                            $tdsAmount = $calculatedAmount * $this->getTDSCharge();
                            $adminCharge = $calculatedAmount * $this->getAdminCharge();
                        }
                        $insertData = [
                            'user_id' => $value->user_id,
                            'promoted_userid' => $memberObj->user_id,
                            'leader_type' => $value->current_level,
                            'bv_requested' => $orderDetails->total_bv,
                            'amount' => $calculatedAmount,
                            'tds' => $tdsAmount,
                            'admin_charge' => $adminCharge,
                            'total_amount' => $calculatedAmount - $adminCharge - $tdsAmount,
                            'created_date' => date('Y-m-d'),
                            'comments' => "The income genereated for " . $memberObj->members_id . " line memebers",
                        ];
                        $this->Common_model->insertPerformanceBonousRecords($insertData);
                        continue;
                    }
                }
                break;
            case '6':
            case '9':
                $dataArr = array_values(array_filter($uplineMember, function($value) { if($value->current_level > '6' && $value->current_level != '9') {return $value;}}));
                $dCount = $this->countArrayKey($mapOnlyCurrLevel, '7');
                $stardCount = $this->countArrayKey($mapOnlyCurrLevel, '10');
                $numItems = count($dataArr);
                $i = 0;
                $isFoundLL = false;
                $isFoundSL = false;
                $isFoundGL = false;
                $isFoundPL = false;
                $isFoundDL = false;
                $isFoundSDL = false;
                $dlCalc = 0.00;
               for ($i = 0; $i < $numItems; $i++) {
                    # code...
                    $value = $dataArr[$i];
                    // Dynamic Leader
                    if (
                        ($dataArr[$i]->current_level == '7' || $dataArr[$i]->current_level == '10') && !$isFoundDL
                    ) {
                        # code...
                        $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('7') - (float)$this->getCurrentBonousStructuralPercentage('6');

                        $isFoundDL = true;
                        // echo $this->calculateCurrentBVPrice($orderDetails->total_bv) . "=>" . $dlCalc . " => " . $value->current_level . "<br>";
                        $calculatedAmount = $this->calculateCurrentBVPrice($orderDetails->total_bv) * $dlCalc;
                        $adminCharge = 0;
                        $tdsAmount = 0;
                        if ($this->getMemberIncomeTotalIncomeReports($memberObj->user_id) > 5000) {
                            $tdsAmount = $calculatedAmount * $this->getTDSCharge();
                            $adminCharge = $calculatedAmount * $this->getAdminCharge();
                        }
                        $insertData = [
                            'user_id' => $value->user_id,
                            'promoted_userid' => $memberObj->user_id,
                            'leader_type' => $value->current_level,
                            'bv_requested' => $orderDetails->total_bv,
                            'amount' => $calculatedAmount,
                            'tds' => $tdsAmount,
                            'admin_charge' => $adminCharge,
                            'total_amount' => $calculatedAmount - $adminCharge - $tdsAmount,
                            'created_date' => date('Y-m-d'),
                            'comments' => "The income genereated for " . $memberObj->members_id . " line memebers",
                        ];
                        $this->Common_model->insertPerformanceBonousRecords($insertData);
                        continue;
                    }

                    //  Super Dynamic Leader Counting 
                    if (
                        ($dataArr[$i]->current_level == '8' || $dataArr[$i]->current_level == '11') && !$isFoundSDL
                    ) {
                        $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('8') - (float)$this->getCurrentBonousStructuralPercentage('6');

                        if ($dCount >= 1 || $stardCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('8') -  (float)$this->getCurrentBonousStructuralPercentage('7'));
                        }
                        // echo $this->calculateCurrentBVPrice($orderDetails->total_bv) . "=>" . $dlCalc . " => " . $value->current_level . "<br>";
                        $isFoundSDL = true;
                        $calculatedAmount = $this->calculateCurrentBVPrice($orderDetails->total_bv) * $dlCalc;
                        $adminCharge = 0;
                        $tdsAmount = 0;
                        if ($this->getMemberIncomeTotalIncomeReports($memberObj->user_id) > 5000) {
                            $tdsAmount = $calculatedAmount * $this->getTDSCharge();
                            $adminCharge = $calculatedAmount * $this->getAdminCharge();
                        }
                        $insertData = [
                            'user_id' => $value->user_id,
                            'promoted_userid' => $memberObj->user_id,
                            'leader_type' => $value->current_level,
                            'bv_requested' => $orderDetails->total_bv,
                            'amount' => $calculatedAmount,
                            'tds' => $tdsAmount,
                            'admin_charge' => $adminCharge,
                            'total_amount' => $calculatedAmount - $adminCharge - $tdsAmount,
                            'created_date' => date('Y-m-d'),
                            'comments' => "The income genereated for " . $memberObj->members_id . " line memebers",
                        ];
                        $this->Common_model->insertPerformanceBonousRecords($insertData);
                        continue;
                    }
                }
                break;
            case '7':
            case '10':
                $dataArr = array_values(array_filter($uplineMember, function($value) { if($value->current_level > '7' && $value->current_level != '10') {return $value;}}));
                
                $numItems = count($dataArr);
                $i = 0;
                $isFoundLL = false;
                $isFoundSL = false;
                $isFoundGL = false;
                $isFoundPL = false;
                $isFoundDL = false;
                $isFoundSDL = false;
                $dlCalc = 0.00;
               for ($i = 0; $i < $numItems; $i++) {
                    # code...
                    $value = $dataArr[$i];
                    //  Super Dynamic Leader Counting 
                    if (
                        ($dataArr[$i]->current_level == '8' || $dataArr[$i]->current_level == '11') && !$isFoundSDL
                    ) {
                        $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('8') - (float)$this->getCurrentBonousStructuralPercentage('7');

                        // echo $this->calculateCurrentBVPrice($orderDetails->total_bv) . "=>" . $dlCalc . " => " . $value->current_level . "<br>";
                      
                        $isFoundSDL = true;
                        $calculatedAmount = $this->calculateCurrentBVPrice($orderDetails->total_bv) * $dlCalc;
                        $adminCharge = 0;
                        $tdsAmount = 0;
                        if ($this->getMemberIncomeTotalIncomeReports($memberObj->user_id) > 5000) {
                            $tdsAmount = $calculatedAmount * $this->getTDSCharge();
                            $adminCharge = $calculatedAmount * $this->getAdminCharge();
                        }
                        $insertData = [
                            'user_id' => $value->user_id,
                            'promoted_userid' => $memberObj->user_id,
                            'leader_type' => $value->current_level,
                            'bv_requested' => $orderDetails->total_bv,
                            'amount' => $calculatedAmount,
                            'tds' => $tdsAmount,
                            'admin_charge' => $adminCharge,
                            'total_amount' => $calculatedAmount - $adminCharge - $tdsAmount,
                            'created_date' => date('Y-m-d'),
                            'comments' => "The income genereated for " . $memberObj->members_id . " line memebers",
                        ];
                        $this->Common_model->insertPerformanceBonousRecords($insertData);
                        continue;
                    }
                }
                break;
            case '8':
            case '11':
                
                break;
            default:
                # code...
                $dataArr = array_values(array_filter($uplineMember, function ($value) {
                    if ($value->current_level > '1') {
                        return $value;
                    }
                }));
                $dCount = $this->countArrayKey($mapOnlyCurrLevel, '7');
                $stardCount = $this->countArrayKey($mapOnlyCurrLevel, '10');
                $pCount = $this->countArrayKey($mapOnlyCurrLevel, '6');
                $starPCount = $this->countArrayKey($mapOnlyCurrLevel, '9');
                $gCount = $this->countArrayKey($mapOnlyCurrLevel, '5');
                $sCount = $this->countArrayKey($mapOnlyCurrLevel, '4');
                $lCount = $this->countArrayKey($mapOnlyCurrLevel, '3');
                $numItems = count($dataArr);
                $bCount = $this->countArrayKey($mapOnlyCurrLevel, '2');
                $isFoundBL = false;
                $isFoundLL = false;
                $isFoundSL = false;
                $isFoundGL = false;
                $isFoundPL = false;
                $isFoundDL = false;
                $isFoundSDL = false;
                $dlCalc = 0.00;
                for ($i = 0; $i < $numItems; $i++) {
                    # code...
                    $value = $dataArr[$i];
                    // Business Leader
                    if ($dataArr[$i]->current_level == '2' && !$isFoundBL
                    ) {
                        # code...
                        $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('2');
                        $isFoundBL = true;
                        // echo $this->calculateCurrentBVPrice($orderDetails->total_bv) . "=>" . $dlCalc . " => " . $value->current_level . "<br>";
                        
                        $calculatedAmount = $this->calculateCurrentBVPrice($orderDetails->total_bv) * $dlCalc;
                        $adminCharge = 0;
                        $tdsAmount = 0;
                        if ($this->getMemberIncomeTotalIncomeReports($memberObj->user_id) > 5000) {
                            $tdsAmount = $calculatedAmount * $this->getTDSCharge();
                            $adminCharge = $calculatedAmount * $this->getAdminCharge();
                        }
                        $insertData = [
                            'user_id' => $value->user_id,
                            'promoted_userid' => $memberObj->user_id,
                            'leader_type' => $value->current_level,
                            'bv_requested' => $orderDetails->total_bv,
                            'amount' => $calculatedAmount,
                            'tds' => $tdsAmount,
                            'admin_charge' => $adminCharge,
                            'total_amount' => $calculatedAmount - $adminCharge - $tdsAmount,
                            'created_date' => date('Y-m-d'),
                            'comments' => "The income genereated for " . $memberObj->members_id . " line memebers",
                        ];
                        $this->Common_model->insertPerformanceBonousRecords($insertData);
                        continue;
                    }
                    // Loyal Leader
                    if ($dataArr[$i]->current_level == '3' && !$isFoundLL) {
                        # code...
                        $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('3');
                        $isFoundLL = true;
                        if ($bCount >= 1) {
                            # code...
                            $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('3') - (float)$this->getCurrentBonousStructuralPercentage('2');
                        }

                        // echo $this->calculateCurrentBVPrice($orderDetails->total_bv) . "=>" . $dlCalc . " => " . $value->current_level . "<br>";

                        $calculatedAmount = $this->calculateCurrentBVPrice($orderDetails->total_bv) * $dlCalc;
                        $adminCharge = 0;
                        $tdsAmount = 0;
                        if ($this->getMemberIncomeTotalIncomeReports($memberObj->user_id) > 5000) {
                            $tdsAmount = $calculatedAmount * $this->getTDSCharge();
                            $adminCharge = $calculatedAmount * $this->getAdminCharge();
                        }
                        $insertData = [
                            'user_id' => $value->user_id,
                            'promoted_userid' => $memberObj->user_id,
                            'leader_type' => $value->current_level,
                            'bv_requested' => $orderDetails->total_bv,
                            'amount' => $calculatedAmount,
                            'tds' => $tdsAmount,
                            'admin_charge' => $adminCharge,
                            'total_amount' => $calculatedAmount - $adminCharge- $tdsAmount,
                            'created_date' => date('Y-m-d'),
                            'comments' => "The income genereated for " . $memberObj->members_id . " line memebers",
                        ];
                        $this->Common_model->insertPerformanceBonousRecords($insertData);
                        continue;
                    }

                    // Silver Leader
                    if (
                        $dataArr[$i]->current_level == '4' && !$isFoundSL
                    ) {
                        # code...
                        $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('4');
                        if ($bCount >= 1) {
                            # code...
                            $dlCalc -= (float)$this->getCurrentBonousStructuralPercentage('2');
                        }
                        if ($lCount >= 1) {
                            $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('4') - (float)$this->getCurrentBonousStructuralPercentage('3');
                        }
                        $isFoundSL = true;
                        // echo $this->calculateCurrentBVPrice($orderDetails->total_bv) . "=>" . $dlCalc . " => " . $value->current_level . "<br>";
                        $calculatedAmount = $this->calculateCurrentBVPrice($orderDetails->total_bv) * $dlCalc;
                        $adminCharge = 0;
                        $tdsAmount = 0;
                        if ($this->getMemberIncomeTotalIncomeReports($memberObj->user_id) > 5000) {
                            $tdsAmount = $calculatedAmount * $this->getTDSCharge();
                            $adminCharge = $calculatedAmount * $this->getAdminCharge();
                        }
                        $insertData = [
                            'user_id' => $value->user_id,
                            'promoted_userid' => $memberObj->user_id,
                            'leader_type' => $value->current_level,
                            'bv_requested' => $orderDetails->total_bv,
                            'amount' => $calculatedAmount,
                            'tds' => $tdsAmount,
                            'admin_charge' => $adminCharge,
                            'total_amount' => $calculatedAmount - $adminCharge - $tdsAmount,
                            'created_date' => date('Y-m-d'),
                            'comments' => "The income genereated for " . $memberObj->members_id . " line memebers",
                        ];
                        $this->Common_model->insertPerformanceBonousRecords($insertData);
                        continue;
                    }
                    // Golden Leader
                    if (
                        $dataArr[$i]->current_level == '5' && !$isFoundGL
                    ) {
                        # code...
                        $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('5');
                        if ($bCount >= 1) {
                            # code...
                            $dlCalc -=  (float)$this->getCurrentBonousStructuralPercentage('2');
                        }
                        if ($lCount >= 1) {

                            $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('5') - (float)$this->getCurrentBonousStructuralPercentage('3');
                        }
                        if ($sCount >= 1) {
                            $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('5') - (float)$this->getCurrentBonousStructuralPercentage('4');
                        }
                        $isFoundGL = true;
                        // echo $this->calculateCurrentBVPrice($orderDetails->total_bv) . "=>" . $dlCalc . " => " . $value->current_level . "<br>";
                        
                        $calculatedAmount = $this->calculateCurrentBVPrice($orderDetails->total_bv) * $dlCalc;
                        $adminCharge = 0;
                        $tdsAmount = 0;
                        if ($this->getMemberIncomeTotalIncomeReports($memberObj->user_id) > 5000) {
                            $tdsAmount = $calculatedAmount * $this->getTDSCharge();
                            $adminCharge = $calculatedAmount * $this->getAdminCharge();
                        }
                        $insertData = [
                            'user_id' => $value->user_id,
                            'promoted_userid' => $memberObj->user_id,
                            'leader_type' => $value->current_level,
                            'bv_requested' => $orderDetails->total_bv,
                            'amount' => $calculatedAmount,
                            'tds' => $tdsAmount,
                            'admin_charge' => $adminCharge,
                            'total_amount' => $calculatedAmount - $adminCharge - $tdsAmount,
                            'created_date' => date('Y-m-d'),
                            'comments' => "The income genereated for " . $memberObj->members_id . " line memebers",
                        ];
                        $this->Common_model->insertPerformanceBonousRecords($insertData);
                        continue;
                    }

                    // Platinum Leader
                    if (
                        ($dataArr[$i]->current_level == '6' || $dataArr[$i]->current_level == '9') && !$isFoundPL
                    ) {
                        # code...
                        $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('6');
                        if ($bCount >= 1) {
                            # code...
                            $dlCalc -=  (float)$this->getCurrentBonousStructuralPercentage('2');
                        }
                        if ($lCount >= 1) {
                            $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('6') - (float)$this->getCurrentBonousStructuralPercentage('3');
                        }
                        if ($sCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('6') -  (float)$this->getCurrentBonousStructuralPercentage('4'));
                        }
                        if ($gCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('6') -  (float)$this->getCurrentBonousStructuralPercentage('5'));
                        }
                        $isFoundPL = true;
                        // echo $this->calculateCurrentBVPrice($orderDetails->total_bv) . "=>" . $dlCalc . " => " . $value->current_level . "<br>";
                        
                        $calculatedAmount = $this->calculateCurrentBVPrice($orderDetails->total_bv) * $dlCalc;
                        $adminCharge = 0;
                        $tdsAmount = 0;
                        if ($this->getMemberIncomeTotalIncomeReports($memberObj->user_id) > 5000) {
                            $tdsAmount = $calculatedAmount * $this->getTDSCharge();
                            $adminCharge = $calculatedAmount * $this->getAdminCharge();
                        }
                        $insertData = [
                            'user_id' => $value->user_id,
                            'promoted_userid' => $memberObj->user_id,
                            'leader_type' => $value->current_level,
                            'bv_requested' => $orderDetails->total_bv,
                            'amount' => $calculatedAmount,
                            'tds' => $tdsAmount,
                            'admin_charge' => $adminCharge,
                            'total_amount' => $calculatedAmount - $adminCharge - $tdsAmount,
                            'created_date' => date('Y-m-d'),
                            'comments' => "The income genereated for " . $memberObj->members_id . " line memebers",
                        ];
                        $this->Common_model->insertPerformanceBonousRecords($insertData);
                        continue;
                    }

                    // Dynamic Leader
                    if (
                        ($dataArr[$i]->current_level == '7' || $dataArr[$i]->current_level == '10') && !$isFoundDL
                    ) {
                        # code...
                        $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('7');
                        if ($bCount >= 1) {
                            # code...
                            $dlCalc -=  (float)$this->getCurrentBonousStructuralPercentage('2');
                        }
                        if ($lCount >= 1) {
                            $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('7') - (float)$this->getCurrentBonousStructuralPercentage('3');
                        }
                        if ($sCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('7') -  (float)$this->getCurrentBonousStructuralPercentage('4'));
                        }
                        if ($gCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('7') -  (float)$this->getCurrentBonousStructuralPercentage('5'));
                        }
                        if ($pCount >= 1 || $starPCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('7') -  (float)$this->getCurrentBonousStructuralPercentage('6'));
                        }
                        $isFoundDL = true;
                        // echo $this->calculateCurrentBVPrice($orderDetails->total_bv) . "=>" . $dlCalc . " => " . $value->current_level . "<br>";
                        
                        $calculatedAmount = $this->calculateCurrentBVPrice($orderDetails->total_bv) * $dlCalc;
                        $adminCharge = 0;
                        $tdsAmount = 0;
                        if ($this->getMemberIncomeTotalIncomeReports($memberObj->user_id) > 5000) {
                            $tdsAmount = $calculatedAmount * $this->getTDSCharge();
                            $adminCharge = $calculatedAmount * $this->getAdminCharge();
                        }
                        $insertData = [
                            'user_id' => $value->user_id,
                            'promoted_userid' => $memberObj->user_id,
                            'leader_type' => $value->current_level,
                            'bv_requested' => $orderDetails->total_bv,
                            'amount' => $calculatedAmount,
                            'tds' => $tdsAmount,
                            'admin_charge' => $adminCharge,
                            'total_amount' => $calculatedAmount - $adminCharge - $tdsAmount,
                            'created_date' => date('Y-m-d'),
                            'comments' => "The income genereated for " . $memberObj->members_id . " line memebers",
                        ];
                        $this->Common_model->insertPerformanceBonousRecords($insertData);
                        continue;
                    }

                    //  Super Dynamic Leader Counting 
                    if (
                        ($dataArr[$i]->current_level == '8' || $dataArr[$i]->current_level == '11') && !$isFoundSDL
                    ) {
                        $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('8');
                        if ($bCount >= 1) {
                            # code...
                            $dlCalc -= (float)$this->getCurrentBonousStructuralPercentage('2');
                        }
                        if ($lCount >= 1) {
                            $dlCalc = (float)$this->getCurrentBonousStructuralPercentage('8') - (float)$this->getCurrentBonousStructuralPercentage('3');
                        }
                        if ($sCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('8') -  (float)$this->getCurrentBonousStructuralPercentage('4'));
                        }
                        if ($gCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('8') -  (float)$this->getCurrentBonousStructuralPercentage('5'));
                        }
                        if ($pCount >= 1 || $starPCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('8') -  (float)$this->getCurrentBonousStructuralPercentage('6'));
                        }
                        if ($dCount >= 1 || $stardCount >= 1) {
                            $dlCalc = ((float)$this->getCurrentBonousStructuralPercentage('8') -  (float)$this->getCurrentBonousStructuralPercentage('7'));
                        }
                        $isFoundSDL = true;
                        // echo $this->calculateCurrentBVPrice($orderDetails->total_bv) . "=>" . $dlCalc . " => " . $value->current_level . "<br>";
                        
                        $calculatedAmount = $this->calculateCurrentBVPrice($orderDetails->total_bv) * $dlCalc;
                        $adminCharge = 0;
                        $tdsAmount = 0;
                        if ($this->getMemberIncomeTotalIncomeReports($memberObj->user_id) > 5000) {
                            $tdsAmount = $calculatedAmount * $this->getTDSCharge();
                            $adminCharge = $calculatedAmount * $this->getAdminCharge();
                        }
                        $insertData = [
                            'user_id' => $value->user_id,
                            'promoted_userid' => $memberObj->user_id,
                            'leader_type' => $value->current_level,
                            'bv_requested' => $orderDetails->total_bv,
                            'amount' => $calculatedAmount,
                            'tds' => $tdsAmount,
                            'admin_charge' => $adminCharge,
                            'total_amount' => $calculatedAmount - $adminCharge - $tdsAmount,
                            'created_date' => date('Y-m-d'),
                            'comments' => "The income genereated for " . $memberObj->members_id . " line memebers",
                        ];
                        $this->Common_model->insertPerformanceBonousRecords($insertData);
                        continue;
                    }
                }
                break;
        }
        
    }
    // Generate Invoice
    public function generateInvoiceOrderUserId($orderObject = null) {
        
        $getfranchiseDetails = $this->Product_model->checkDuplicateUserEntryFranchise('',$orderObject->franchise_id);
        
        $getOrderProducts = $this->Product_model->getOrderProductsRelational($orderObject->id);
        $getOrderPackageDetails = $this->Product_model->getOrderPackagesRelational($orderObject->id);
        // echo "<pre>";
        // print_r (substar($getfranchiseDetails[0]->members_id,5));
        // echo "</pre>";exit;
         
        // echo "<br>====================== Product Record ========================= <br>";
        
        // echo "<pre>";
        // print_r ($getOrderProducts);
        // echo "</pre>";

        // echo "<br>====================== Package Record ========================= <br>";
        // echo "<pre>";
        // print_r($getOrderPackageDetails);
        // echo "</pre>";
        
        
        $total_cgst = 0;
        $total_sgst = 0;
        $total_igst = 0;
        $insertInvoiceData  = [
            'order_id' => $orderObject->id,
            'created_date' => date('Y-m-d H:i:s')
        ];
        $invoiceId = $this->Product_model->insertInvoicePartData($insertInvoiceData);
        if($invoiceId) {
            $getFranchiseAddress = $this->Common_model->getFranchiseDetails($orderObject->franchise_id);
            $getMembersAddress = $this->Common_model->getCustomMemSelectDataByUserId($orderObject->mem_id, 'state');
            $isCalculateBasedOnGST = $getFranchiseAddress[0]->state == $getMembersAddress[0]->state ? true : false;
            foreach ($getOrderProducts as $key => $value) {
                # code...
                $getHSNCodeDetails = $this->Product_model->getGSTByHSNcodeById($value->hsn_code)[0];
                $beforeGST = $value->dp_amount * $value->qty;
                if ($isCalculateBasedOnGST) {
                    if ($getHSNCodeDetails->igst && (!$getHSNCodeDetails->cgst && !$getHSNCodeDetails->sgst)) {
                        # code...
                        $devidiingPercenttage = $getHSNCodeDetails->igst / 2;
                        $getHSNCodeDetails->cgst = $devidiingPercenttage;
                        $getHSNCodeDetails->sgst = $devidiingPercenttage;
                    }
                    $getHSNCodeDetails->igst = 0;
                } else {
                    if (!$getHSNCodeDetails->igst) {
                        # code...
                        $getHSNCodeDetails->igst  = $getHSNCodeDetails->cgst + $getHSNCodeDetails->sgst;
                    }
                    $getHSNCodeDetails->cgst = 0;
                    $getHSNCodeDetails->sgst = 0;
                }
                $calculatedGST = ($beforeGST) - (($beforeGST / (100 + ($getHSNCodeDetails->cgst + $getHSNCodeDetails->sgst))) * 100);
                $cgst = $calculatedGST / 2;
                $sgst = $calculatedGST / 2;
                $igst = ($beforeGST) - (($beforeGST / (100 + $getHSNCodeDetails->igst)) * 100);
                $total_cgst += $cgst;
                $total_sgst += $sgst;
                $total_igst += $igst;
                $insertProductData = [
                    'invoice_id'   => $invoiceId,
                    'product_id'   => $value->product_id,
                    'product_name' => $value->product_name,
                    'mrp'  => $value->mrp_amount,
                    'dp'   => $value->dp_amount,
                    'bv'   => $value->bv_amout,
                    'hsn'  => $getHSNCodeDetails->hsn_code,
                    'cgst' => $getHSNCodeDetails->cgst,
                    'sgst' => $getHSNCodeDetails->sgst,
                    'igst' => $getHSNCodeDetails->igst,
                    'created_date' => date('Y-m-d H:i:s'),
                ];
                $this->Product_model->insertOrderInvoiceData($insertProductData);
            }

            foreach ($getOrderPackageDetails as $key => $value) {
                # code...
                $getOrderSynToPackage = $this->Product_model->loadPackageProduct($value->package_id);
                foreach ($getOrderSynToPackage as  $pvalue) {
                    # code...
                    $pvalue->hsn_code = $this->Product_model->getProductDetails($pvalue->product_id)[0]->hsn_code;
                    $getHSNCodeDetails = $this->Product_model->getGSTByHSNcodeById($pvalue->hsn_code)[0];
                    
                    $beforeGST = $pvalue->dp * $value->qty;
                    if ($isCalculateBasedOnGST) {
                        if ($getHSNCodeDetails->igst && (!$getHSNCodeDetails->cgst && !$getHSNCodeDetails->sgst)
                        ) {
                            # code...
                            $devidiingPercenttage = $getHSNCodeDetails->igst / 2;
                            $getHSNCodeDetails->cgst = $devidiingPercenttage;
                            $getHSNCodeDetails->sgst = $devidiingPercenttage;
                        }
                        $getHSNCodeDetails->igst = 0;
                    } else {
                        if (!$getHSNCodeDetails->igst) {
                            # code...
                            $getHSNCodeDetails->igst  = $getHSNCodeDetails->cgst + $getHSNCodeDetails->sgst;
                        }
                        $getHSNCodeDetails->cgst = 0;
                        $getHSNCodeDetails->sgst = 0;
                    }
                    $calculatedGST = ($beforeGST) - (($beforeGST / (100 + ($getHSNCodeDetails->cgst + $getHSNCodeDetails->sgst))) * 100);
                    $cgst = $calculatedGST / 2;
                    $sgst = $calculatedGST / 2;
                    $igst = ($beforeGST) - (($beforeGST / (100 + $getHSNCodeDetails->igst)) * 100);
                    $total_cgst += $cgst;
                    $total_sgst += $sgst;
                    $total_igst += $igst;
                    $insertProductData = [
                        'invoice_id'   => $invoiceId,
                        'product_id'   => $pvalue->product_id,
                        'package_id'   => $value->package_id,
                        'product_name' => $pvalue->product_name,
                        'mrp'  => $pvalue->mrp,
                        'dp'   => $pvalue->dp,
                        'bv'   => $pvalue->bv_bonous,
                        'hsn'  => $getHSNCodeDetails->hsn_code,
                        'cgst' => $getHSNCodeDetails->cgst,
                        'sgst' => $getHSNCodeDetails->sgst,
                        'igst' => $getHSNCodeDetails->igst,
                        'created_date' => date('Y-m-d H:i:s'),
                    ];
                    $this->Product_model->insertOrderInvoiceData($insertProductData);
                }
            }
            $uniqueFranchise = count($this->Product_model->findnewuniquefranchise(substr($getfranchiseDetails[0]->members_id,5)));
            $updatedData = [
                'invoice_id' => "INVOICE" . date('Y') . "-" . sprintf("%04d", $invoiceId),
                'new_invoice' => "INVOICE" . date('Y') . "-" .substr($getfranchiseDetails[0]->members_id,5). "-" . sprintf("%04d", $uniqueFranchise+1),
                'total_cgst' => $total_cgst,
                'total_sgst' => $total_sgst,
                'total_igst' => $total_igst,
                'net_amount' => ($orderObject->total_dp) - $total_cgst - $total_sgst - $total_igst,
                'created_date' => date('Y-m-d H:i:s')
            ];

            $creditRequestUpdate = [
                'user_id' => $orderObject->franchise_id,
                'amount' => $orderObject->total_dp,
                'comments' => "The amount deduction has been done because of <strong>" .$updatedData['new_invoice']. "</strong> has been generated.",
                'created_date' => date('Y-m-d H:i:s')
            ];
            $this->Common_model->insertFranchiseCreditReqAmount($creditRequestUpdate);
            // echo "<br> ============================= <br>";
            // echo "<pre>";
            // print_r ($updatedData);
            // echo "</pre>";
            $this->Product_model->updateInsertInvoiceData($updatedData, $invoiceId);
        }
    }

    public function showInvoiceReports($invoiceId = '') {
       
        if ($this->isAdmin() || $this->isSuperAdmin() || $this->isFranchise() || $this->isSuperFrnachise()) {
            # code...
            $invoiceRecords = $this->Product_model->getInvoiceRecords($invoiceId);
            
            if (empty($invoiceRecords)) {
                # code...
                $this->setSessionNotification('wp_page', false, 'error', 'Invalid details given. please try again later.');
                return $this->redirectToUrl('wp-dashboard/invoice/reports');
            }
            $getFranchiseDetails = $this->Common_model->getKYCDetailsByUserIdFrachise($invoiceRecords[0]->franchise_id);
            $getMemberDetails    = $this->Common_model->getKYCDetailsByUserId($invoiceRecords[0]->mem_id);
            $getOrderList        = $this->Product_model->getOrderProductsInv($invoiceRecords[0]->order_id);
            $getPackageList      = $this->Product_model->getOrderPackagesInv($invoiceRecords[0]->order_id);
                
            
            
            $getFranchiseDetails[0]->city = $this->Common_model->getCityName($getFranchiseDetails[0]->city)[0]->city;
            $getFranchiseDetails[0]->state = $this->Common_model->getStateName($getFranchiseDetails[0]->state)[0]->name;
            $getMemberDetails[0]->city = $getMemberDetails[0]->city == '0' ? "" : $this->Common_model->getCityName($getMemberDetails[0]->city)[0]->city;
            $getMemberDetails[0]->state = $this->Common_model->getStateName($getMemberDetails[0]->state)[0]->name;
            $isRefundedStatus = false;
            $totalQty = $totalBV = $totalDP =0;
            foreach ($getOrderList as $key => $value) {
                # code...
                $orderList = $this->Product_model->getInvoiceProductOrder($invoiceRecords[0]->id, $value->product_id);
                $productDetails = $this->Product_model->getProductDetails($value->product_id)[0];
                $value->sku = $productDetails->product_sku;
                if($value->total_quantity != ''){
                            $totalDP += ((int)($value->total_quantity)-(int)($value->qty))*(float)($value->dp_amount);
                        }
                        else{
                            $totalDP += 0;
                        }
                if (!empty($orderList)) {
                    # code...
                    
                    $value->product_details = $orderList[0];
                }
                if ($value->status == '1') {
                    # code...
                    $isRefundedStatus = true;
                }
            }
            $productItem  = 0;
            foreach ($getPackageList as $key => $value) {
                # code...
                
                $packageList = $this->Product_model->getInvoicePackageOrder($invoiceRecords[0]->id, $value->package_id);
                $productItem += count($packageList);
                $value->package_name = $this->Product_model->getPackageDetails($value->package_id)[0]->package_name;
                $value->product_item = count($packageList);
                $value->product_details = [];
                if($value->total_quantity != ''){
                            $totalDP += ((int)($value->total_quantity)-(int)($value->qty))*(float)($value->dp_amount);
                        }
                        else{
                            $totalDP += 0;
                        }
                foreach ($packageList as $Pvalue) {
                    # code...
                    $productDetails = $this->Product_model->getProductDetails($Pvalue->product_id)[0];
                    $Pvalue->sku = $productDetails->product_sku;
                    // $finalObj = new stdClass();
                    // $finalObj->packageObj = $value;
                    // $finalObj->packageDetails = $Pvalue;
                    array_push($value->product_details, $Pvalue);
                }
                if ($value->status == '1') {
                    # code...
                    $isRefundedStatus = true;
                }
            }
            // echo "<pre>";
            // print_r ($getPackageList);
            // echo "<br>================================</br>";
            // print_r ($getFranchiseDetails);
            // echo "<br>================================</br>";
            // print_r ($getMemberDetails);
            // echo "<br>================PACKAGE================</br>";
            // print_r ($getPackageList);
            // // echo "<br>================ORDER================</br>";
            // print_r ($getOrderList);

            // echo "</pre>";
            // exit;
            $invoiceRecords[0]->totalDPr = $totalDP;
            $data = [
                'invoiceRecords' => $invoiceRecords[0],
                'memeber_details' => $getMemberDetails[0],
                'franchise_details' => $getFranchiseDetails[0],
                'product_order' => $getOrderList,
                'productItem' => $productItem,
                'package_list' => $getPackageList,
                'current_invoice' => true,
                'refund_status' => $isRefundedStatus,
            ];
            
             
            if ($this->request->getGet('load_refund') == 'true') {
                # code...
                return  $this->adminView('invoice-details-return', 'Products/Invoice', $data);
            } elseif($this->request->getGet('buy_back_attachment') == 'true') {
                $data ['buyBackDetails'] = $this->Product_model->getBuyBackDetails($invoiceRecords[0]->id);
                
                return  $this->adminView('buy-back-return-attachment', 'Products/Invoice', $data);
            }
            return  $this->adminView('invoice-details', 'Products/Invoice', $data);
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function returnInvoiceOrder($invoiceId = '') {
        
        if ($this->isAdmin() || $this->isSuperAdmin() || $this->isFranchise() || $this->isSuperFrnachise()) {
            $invoiceRecords = $this->Product_model->getInvoiceRecords($invoiceId);
            
            if (empty($invoiceRecords)) {
                # code...
                $this->setSessionNotification('wp_page', false, 'error', 'Invalid details given. please try again later.');
                return $this->redirectToUrl('wp-dashboard/invoice/reports');
            }
            $invoiceRecords[0]->tDP = 0.00;
            $invoiceRecords[0]->tBV = 0.00;
            $getMemberDetails    = $this->Common_model->getKYCDetailsByUserId($invoiceRecords[0]->mem_id);
            $getOrderList        = $this->Product_model->getOrderProductsInv($invoiceRecords[0]->order_id);
            $getPackageList      = $this->Product_model->getOrderPackagesInv($invoiceRecords[0]->order_id);
            
            foreach ($getOrderList as $key => $value) {
                # code...
                $invoiceRecords[0]->tDP += (int)($value->qty)*(float)($value->dp_amount);
                $invoiceRecords[0]->tBV += (int)($value->qty)*(float)($value->bv_amout);
                $orderList = $this->Product_model->getInvoiceProductOrder($invoiceRecords[0]->id, $value->product_id);
                $productDetails = $this->Product_model->getProductDetails($value->product_id)[0];
                $value->sku = $productDetails->product_sku;
                if (!empty($orderList)) {
                    # code...
                    $value->product_details = $orderList[0];
                }
            }
            $productItem  = 0;
            foreach ($getPackageList as $key => $value) {
                # code...
                $invoiceRecords[0]->tDP += (int)($value->qty)*(float)($value->dp_amount);
                $invoiceRecords[0]->tBV += (int)($value->qty)*(float)($value->bv_amout);
                $packageList = $this->Product_model->getInvoicePackageOrder($invoiceRecords[0]->id, $value->package_id);
                $productItem += count($packageList);
                $value->package_name = $this->Product_model->getPackageDetails($value->package_id)[0]->package_name;
                $value->product_item = count($packageList);
                $value->product_details = [];
                foreach ($packageList as $Pvalue) {
                    # code...
                    $productDetails = $this->Product_model->getProductDetails($Pvalue->product_id)[0];
                    $Pvalue->sku = $productDetails->product_sku;
                    // $finalObj = new stdClass();
                    // $finalObj->packageObj = $value;
                    // $finalObj->packageDetails = $Pvalue;
                    array_push($value->product_details, $Pvalue);
                }
            }
            $data = [
                'invoice_details' => $invoiceRecords[0],
                'memeber_details' => $getMemberDetails[0],
                'product_list' => $getOrderList,
                'package_list' => $getPackageList,
            ];
            
            // echo "<pre>";
            // print_r ($data);
            // echo "</pre>";
            // exit;
            return  $this->adminView('return-invoice-details', 'Products/Invoice', $data);

        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function showInvoiceReportsFromOrder($orderId = '')
    {
        if ($this->isAdmin() || $this->isSuperAdmin() || $this->isFranchise() || $this->isSuperFrnachise()) {
            # code...
            $invoiceRecords = $this->Product_model->getInvoiceRecordsOrder($orderId);
            if (empty($invoiceRecords)) {
                # code...
                $this->setSessionNotification('wp_page', false, 'error', 'Invalid details given. please try again later.');
                return $this->redirectToUrl('wp-dashboard/invoice/orders-list');
            }
            $getFranchiseDetails = $this->Common_model->getKYCDetailsByUserIdFrachise($invoiceRecords[0]->franchise_id);
            $getMemberDetails    = $this->Common_model->getKYCDetailsByUserId($invoiceRecords[0]->mem_id);
            $getOrderList        = $this->Product_model->getOrderProducts($invoiceRecords[0]->order_id);
            $getPackageList      = $this->Product_model->getOrderPackages($invoiceRecords[0]->order_id);
            $getFranchiseDetails[0]->city = $this->Common_model->getCityName($getFranchiseDetails[0]->city)[0]->city;
            $getFranchiseDetails[0]->state = $this->Common_model->getStateName($getFranchiseDetails[0]->state)[0]->name;
            $getMemberDetails[0]->city = $this->Common_model->getCityName($getMemberDetails[0]->city)[0]->city;
            $getMemberDetails[0]->state = $this->Common_model->getStateName($getMemberDetails[0]->state)[0]->name;
            $getInvoicePackage   = [];
            foreach ($getOrderList as $key => $value) {
                # code...
                $orderList = $this->Product_model->getInvoiceProductOrder($invoiceRecords[0]->id, $value->product_id);
                $productDetails = $this->Product_model->getProductDetails($value->product_id)[0];
                $value->sku = $productDetails->product_sku;
                if (!empty($orderList)) {
                    # code...
                    $value->product_details = $orderList[0];
                }
            }
            $productItem  = 0;
            foreach ($getPackageList as $key => $value) {
                # code...
                $packageList = $this->Product_model->getInvoicePackageOrder($invoiceRecords[0]->id, $value->package_id);
                $productItem += count($packageList);
                $value->package_name = $this->Product_model->getPackageDetails($value->package_id)[0]->package_name;
                $value->product_item = count($packageList);
                $value->product_details = [];
                foreach ($packageList as $Pvalue) {
                    # code...
                    $productDetails = $this->Product_model->getProductDetails($Pvalue->product_id)[0];
                    $Pvalue->sku = $productDetails->product_sku;
                    // $finalObj = new stdClass();
                    // $finalObj->packageObj = $value;
                    // $finalObj->packageDetails = $Pvalue;
                    array_push($value->product_details, $Pvalue);
                }
            }
            // echo "<pre>";
            // print_r ($invoiceRecords);
            // echo "<br>================================</br>";
            // print_r ($getFranchiseDetails);
            // echo "<br>================================</br>";
            // print_r ($getMemberDetails);
            // echo "<br>================PACKAGE================</br>";
            // print_r ($getPackageList);
            // echo "<br>================ORDER================</br>";
            // 
            
            $data = [
                'invoiceRecords' => $invoiceRecords[0],
                'memeber_details' => $getMemberDetails[0],
                'franchise_details' => $getFranchiseDetails[0],
                'product_order' => $getOrderList,
                'productItem' => $productItem,
                'package_list' => $getPackageList,
                'current_invoice' => true,
                'from_product' => true
            ];
            // 
            return  $this->adminView('invoice-details', 'Products/Invoice', $data);
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    private function generateInvoiceForFranchiseAccess($orderObject = null) {
        
        $getOrderProducts = $this->Product_model->getOrderProductsRelationalFran($orderObject->id);
        $getOrderPackageDetails = $this->Product_model->getOrderPackagesRelationalFran($orderObject->id);
        // echo "<pre>";
        // print_r ($orderObject);
        // echo "</pre>";
        // echo "<br>====================== Product Record ========================= <br>";

        // echo "<pre>";
        // print_r ($getOrderProducts);
        // echo "</pre>";

        // echo "<br>====================== Package Record ========================= <br>";
        // echo "<pre>";
        // print_r($getOrderPackageDetails);
        // echo "</pre>";
        $total_cgst = 0;
        $total_sgst = 0;
        $total_igst = 0;
        // exit;
        $insertInvoiceData  = [
            'transation_id' => $orderObject->id,
            'created_date' => date('Y-m-d H:i:s')
        ];
        $invoiceId = $this->Product_model->insertInvoicePartDataFranchise($insertInvoiceData);
        if ($invoiceId) {
            $getFranchiseAddress = $this->Common_model->getFranchiseDetails($orderObject->franchise_id);
            $isCalculateBasedOnGST = $getFranchiseAddress[0]->state == '17' ? true : false;
            foreach ($getOrderProducts as $key => $value) {
                # code...
                $getHSNCodeDetails = $this->Product_model->getGSTByHSNcodeById($value->hsn_code)[0];
                $beforeGST = $value->dp_amount * $value->qty;
                if ($isCalculateBasedOnGST) {
                    if ($getHSNCodeDetails->igst && (!$getHSNCodeDetails->cgst && !$getHSNCodeDetails->sgst)) {
                        # code...
                        $devidiingPercenttage = $getHSNCodeDetails->igst / 2;
                        $getHSNCodeDetails->cgst = $devidiingPercenttage;
                        $getHSNCodeDetails->sgst = $devidiingPercenttage;
                    }
                    $getHSNCodeDetails->igst = 0;
                } else {
                    if (!$getHSNCodeDetails->igst) {
                        # code...
                        $getHSNCodeDetails->igst  = $getHSNCodeDetails->cgst + $getHSNCodeDetails->sgst;
                    }
                    $getHSNCodeDetails->cgst = 0;
                    $getHSNCodeDetails->sgst = 0;
                }
                $calculatedGST = ($beforeGST) - (($beforeGST / (100 + ($getHSNCodeDetails->cgst + $getHSNCodeDetails->sgst))) * 100);
                $cgst = $calculatedGST / 2;
                $sgst = $calculatedGST / 2;
                $igst = ($beforeGST) - (($beforeGST / (100 + $getHSNCodeDetails->igst)) * 100);
                $total_cgst += $cgst;
                $total_sgst += $sgst;
                $total_igst += $igst;
                $insertProductData = [
                    'invoice_id'   => $invoiceId,
                    'product_id'   => $value->product_id,
                    'product_name' => $value->product_name,
                    'mrp'  => $value->mrp_amount,
                    'dp'   => $value->dp_amount,
                    'bv'   => $value->bv_amout,
                    'hsn'  => $getHSNCodeDetails->hsn_code,
                    'cgst' => $getHSNCodeDetails->cgst ? $getHSNCodeDetails->cgst : 0.00,
                    'sgst' => $getHSNCodeDetails->sgst ? $getHSNCodeDetails->sgst : 0.00,
                    'igst' => $getHSNCodeDetails->igst ? $getHSNCodeDetails->igst : 0.00,
                    'created_date' => date('Y-m-d H:i:s'),
                ];
                $this->Product_model->insertOrderInvoiceDataFranchise($insertProductData);
            }

            foreach ($getOrderPackageDetails as $key => $value) {
                # code...
                $getOrderSynToPackage = $this->Product_model->loadPackageProduct($value->package_id);
                foreach ($getOrderSynToPackage as  $pvalue) {
                    # code...
                    $pvalue->hsn_code = $this->Product_model->getProductDetails($pvalue->product_id)[0]->hsn_code;
                    $getHSNCodeDetails = $this->Product_model->getGSTByHSNcodeById($pvalue->hsn_code)[0];
                    $beforeGST = $pvalue->dp * $value->qty;
                    if ($isCalculateBasedOnGST) {
                        if ($getHSNCodeDetails->igst && (!$getHSNCodeDetails->cgst && !$getHSNCodeDetails->sgst)
                        ) {
                            # code...
                            $devidiingPercenttage = $getHSNCodeDetails->igst / 2;
                            $getHSNCodeDetails->cgst = $devidiingPercenttage;
                            $getHSNCodeDetails->sgst = $devidiingPercenttage;
                        }
                        $getHSNCodeDetails->igst = 0;
                    } else {
                        if (!$getHSNCodeDetails->igst) {
                            # code...
                            $getHSNCodeDetails->igst  = $getHSNCodeDetails->cgst + $getHSNCodeDetails->sgst;
                        }
                        $getHSNCodeDetails->cgst = 0;
                        $getHSNCodeDetails->sgst = 0;
                    }
                    $calculatedGST = ($beforeGST) - (($beforeGST / (100 + ($getHSNCodeDetails->cgst + $getHSNCodeDetails->sgst))) * 100);
                    $cgst = $calculatedGST / 2;
                    $sgst = $calculatedGST / 2;
                    $igst = ($beforeGST) - (($beforeGST / (100 + $getHSNCodeDetails->igst)) * 100);
                    $total_cgst += $cgst;
                    $total_sgst += $sgst;
                    $total_igst += $igst;
                    $insertProductData = [
                        'invoice_id'   => $invoiceId,
                        'product_id'   => $pvalue->product_id,
                        'package_id'   => $value->package_id,
                        'product_name' => $pvalue->product_name,
                        'mrp'  => $pvalue->mrp,
                        'dp'   => $pvalue->dp,
                        'bv'   => $pvalue->bv_bonous,
                        'hsn'  => $getHSNCodeDetails->hsn_code,
                        'cgst' => $getHSNCodeDetails->cgst ? $getHSNCodeDetails->cgst : 0.00,
                        'sgst' => $getHSNCodeDetails->sgst ? $getHSNCodeDetails->sgst : 0.00,
                        'igst' => $getHSNCodeDetails->igst ? $getHSNCodeDetails->igst : 0.00,
                        'created_date' => date('Y-m-d H:i:s'),
                    ];
                    $this->Product_model->insertOrderInvoiceDataFranchise($insertProductData);
                }
            }

            $updatedData = [
                'invoice_id' => "INVOICE" . date('Y') . "- ". sprintf("%04d", $invoiceId),
                'total_cgst' => $total_cgst,
                'total_sgst' => $total_sgst,
                'total_igst' => $total_igst,
                'net_amount' => ($orderObject->total_dp) - $total_cgst - $total_sgst - $total_igst,
                'created_date' => date('Y-m-d H:i:s')
            ];
            // echo "<br> ============================= <br>";
            // echo "<pre>";
            // print_r ($updatedData);
            // echo "</pre>";
            $this->Product_model->updateInsertInvoiceDataFranchise($updatedData, $invoiceId);
    }
}

    public function franchiseLoadInvoice() {
        if ($this->isAdmin() || $this->isSuperAdmin() || $this->isSuperFrnachise()) {
            # code...
            $loadReports = $this->Product_model->getInvoiceRecordsFranchise();
            
            foreach ($loadReports as $key => $value) {
                # code...
                $value->totalQty = 0;
                $value->total_BV = 0.00;
                $value->total_MRP = 0.00;
                $packageList = $this->Product_model->getOrderPackages($value->order_id);
                $productList = $this->Product_model->getOrderProducts($value->order_id);
                
                if (!empty($productList)) {
                    # code...
                   
                    foreach ($productList as $Pkey => $Pvalue) {
                        # code...
                        $value->totalQty += (int)($Pvalue->qty);
                        $value->total_BV += (int)($Pvalue->qty)*(float)($Pvalue->bv_amout);
                        $value->total_MRP += (int)($Pvalue->qty)*(float)($Pvalue->dp_amount);
                    }
                    
                }
                

                if (!empty($packageList)) {
                    # code...
                    foreach ($packageList as $cKey => $Cvalue) {
                        # code...
                         $value->totalQty += (int)($Cvalue->qty);
                         $value->total_BV += (int)($Cvalue->qty)*(float)($Cvalue->bv_amout);
                        $value->total_MRP += (int)($Cvalue->qty)*(float)($Cvalue->dp_amount);
                    }
                }
                $memberDetails = $this->Common_model->getCustomFranchiseLoginDetails($value->franchise_id, 'user_name')[0];
                $value->full_name = $memberDetails->user_name;
            }
            
            
            $data = [
                'currentPage'         => '/wp-dashboard/invoice/reports',
                'pageName'            => 'Reports',
                'transation_list'     => $loadReports
            ];
            // echo "<pre>";
            // print_r($loadReports);
            // echo "</pre>";exit;
            return $this->adminView('order-reports', 'Products/Stocks/Admin/', $data);
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function franchiseInvoiceDetails($invoiceId = '') {
        if ($this->isAdmin() || $this->isSuperAdmin()|| $this->isSuperFrnachise()) {
            # code...
            if ($this->request->getGet('type') == 'refunded' || $this->request->getGet('type') == 'creditnote' ) {
                $invoiceRecords = $this->Product_model->getInvoiceRecordsFranchise($invoiceId);
                if (empty($invoiceRecords)) {
                    # code...
                    $this->setSessionNotification('wp_page', false, 'error', 'Invalid details given. please try again later.');
                    return $this->redirectToUrl('wp-dashboard/franchise-settings/stocks/invoice-list');
                }
                $getFranchiseDetails = $this->Common_model->getKYCDetailsByUserIdFrachise($invoiceRecords[0]->franchise_id);
                $getMemberDetails    = $this->Common_model->checkAdminUserIdExit($invoiceRecords[0]->updated_userid);
                $getOrderList        = $this->Product_model->getOrderProductsRelationalFran($invoiceRecords[0]->order_id);
                $getPackageList      = $this->Product_model->getOrderPackagesRelationalFran($invoiceRecords[0]->order_id);
                
                $getFranchiseDetails[0]->city = $this->Common_model->getCityName($getFranchiseDetails[0]->city)[0]->city;
                $getFranchiseDetails[0]->state = $this->Common_model->getStateName($getFranchiseDetails[0]->state)[0]->name;
                $getInvoicePackage   = [];
                
                foreach ($getOrderList as $key => $value) {
                    # code...
                    
                    if($value->total_qty == ''){
                        $invHis = $this->Product_model->loadProductInvHistory($value->product_id,$invoiceRecords[0]->invoice_id);
                        $value->qty = empty($invHis)?0:$invHis[0]->added_stocks;
                    }
                    else{
                        $value->qty = $value->total_qty-$value->qty;
                    }
                    
                    $orderList = $this->Product_model->getInvoiceProductOrderFranchise($invoiceRecords[0]->id, $value->product_id);
                    $productDetails = $this->Product_model->getProductDetails($value->product_id)[0];
                    $value->sku = $productDetails->product_sku;
                    if (!empty($orderList)) {
                        # code...
                        $value->product_details = $orderList[0];
                    }
                }
                
                
                
                $productItem  = 0;
                foreach ($getPackageList as $key => $value) {
                    # code...
                    $packageList = $this->Product_model->getInvoicePackageOrderFranchise($invoiceRecords[0]->id, $value->package_id);
                    $productItem += count($packageList);
                    $value->product_item = count($packageList);
                    $value->qty = ($value->total_qty !='')? $value->total_qty-$value->qty:$value->qty;
                    $value->product_details = [];
                    foreach ($packageList as $Pvalue) {
                        # code...
                        $productDetails = $this->Product_model->getProductDetails($Pvalue->product_id)[0];
                        $Pvalue->sku = $productDetails->product_sku;
                        // $finalObj = new stdClass();
                        // $finalObj->packageObj = $value;
                        // $finalObj->packageDetails = $Pvalue;
                        array_push($value->product_details, $Pvalue);
                    }
                }
                
                $date1 = $invoiceRecords[0]->created_date;
                $date2 = $invoiceRecords[0]->updated_date;
                
                $ts1 = strtotime($date1);
                $ts2 = strtotime($date2);
                                            
                $year1 = date('Y', $ts1);
                $year2 = date('Y', $ts2);
                
                $month1 = date('m', $ts1);
                $month2 = date('m', $ts2);
                                            
                
                $diff = (($year2 - $year1) * 12) + ($month2 - $month1);
                
                
                $data = [
                    'invoiceRecords' => $invoiceRecords[0],
                    'memeber_details' => $getMemberDetails[0],
                    'franchise_details' => $getFranchiseDetails[0],
                    'product_order' => $getOrderList,
                    'productItem' => $productItem,
                    'package_list' => $getPackageList,
                    'current_invoice' => true,
                    'heading' => ($diff > 0) ? "Credit Note":"Refund Invoic"
                ];
                
            //     echo "<pre>";
            //     print_r($data['package_list']);
            //     echo "</pre>";
                
            //   exit;
                
                    # code...
                    if($this->request->getGet('type') == 'creditnote'){
                        return  $this->adminView('invoice-credit-note', 'Products/Stocks/Admin', $data);
                    }
                    else{
                        return  $this->adminView('invoice-refund-details', 'Products/Stocks/Admin', $data);
                    }
            }
            else {
                $invoiceRecords = $this->Product_model->getInvoiceRecordsFranchise($invoiceId);
                if (empty($invoiceRecords)) {
                    # code...
                    $this->setSessionNotification('wp_page', false, 'error', 'Invalid details given. please try again later.');
                    return $this->redirectToUrl('wp-dashboard/franchise-settings/stocks/invoice-list');
                }
                $getFranchiseDetails = $this->Common_model->getKYCDetailsByUserIdFrachise($invoiceRecords[0]->franchise_id);
                $getMemberDetails    = $this->Common_model->checkAdminUserIdExit($invoiceRecords[0]->updated_userid);
                $getOrderList        = $this->Product_model->getOrderProductsRelationalFran($invoiceRecords[0]->order_id);
                $getPackageList      = $this->Product_model->getOrderPackagesRelationalFran($invoiceRecords[0]->order_id);
                $getFranchiseDetails[0]->city = $this->Common_model->getCityName($getFranchiseDetails[0]->city)[0]->city;
                $getFranchiseDetails[0]->state = $this->Common_model->getStateName($getFranchiseDetails[0]->state)[0]->name;
                $getInvoicePackage   = [];
                
                // print_r($value);exit;
                
                // print_r($invoiceRecords);exit;
                
                foreach ($getOrderList as $key => $value) {
                    # code...
                    
                    
                    $orderList = $this->Product_model->getInvoiceProductOrderFranchise($invoiceRecords[0]->id, $value->product_id);
                    $productDetails = $this->Product_model->getProductDetails($value->product_id)[0];
                    $value->sku = $productDetails->product_sku;
                    if (!empty($orderList)) {
                        # code...
                        $value->product_details = $orderList[0];
                    }
                }
                $productItem  = 0;
                foreach ($getPackageList as $key => $value) {
                    # code...
                    $packageList = $this->Product_model->getInvoicePackageOrderFranchise($invoiceRecords[0]->id, $value->package_id);
                    $productItem += count($packageList);
                    $value->product_item = count($packageList);
                    $value->product_details = [];
                    foreach ($packageList as $Pvalue) {
                        # code...
                        $productDetails = $this->Product_model->getProductDetails($Pvalue->product_id)[0];
                        $Pvalue->sku = $productDetails->product_sku;
                        // $finalObj = new stdClass();
                        // $finalObj->packageObj = $value;
                        // $finalObj->packageDetails = $Pvalue;
                        array_push($value->product_details, $Pvalue);
                    }
                }
                
                
                
                $data = [
                    'invoiceRecords' => $invoiceRecords[0],
                    'memeber_details' => $getMemberDetails[0],
                    'franchise_details' => $getFranchiseDetails[0],
                    'product_order' => $getOrderList,
                    'productItem' => $productItem,
                    'package_list' => $getPackageList,
                    'current_invoice' => true
                ];
                
                // echo "<pre>";
                // print_r ($data);
                // echo "</pre>";
                // exit;
                
                    # code...
                return  $this->adminView('invoice-details', 'Products/Stocks/Admin', $data);
            }
            
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function franchiseInvoiceReturn($invoiceId = '') {
        if ($this->isAdmin() || $this->isSuperAdmin()) {
            $invoiceRecords = $this->Product_model->getInvoiceRecordsFranchise($invoiceId);
            if (empty($invoiceRecords)) {
                # code...
                $this->setSessionNotification('wp_page', false, 'error', 'Invalid details given. please try again later.');
                return $this->redirectToUrl('wp-dashboard/franchise-settings/stocks/invoice-list');
            }
            
            $getOrderList        = $this->Product_model->getOrderProductsRelationalFran($invoiceRecords[0]->order_id);
            $getPackageList      = $this->Product_model->getOrderPackagesRelationalFran($invoiceRecords[0]->order_id);
            foreach ($getOrderList as $key => $value) {
                # code...
                $orderList = $this->Product_model->getInvoiceProductOrderFranchise($invoiceRecords[0]->id, $value->product_id);
                $productDetails = $this->Product_model->getProductDetails($value->product_id)[0];
                $value->sku = $productDetails->product_sku;
                if (!empty($orderList)) {
                    # code...
                    $value->product_details = $orderList[0];
                }
            }
            $productItem  = 0;
            foreach ($getPackageList as $key => $value) {
                # code...
                $packageList = $this->Product_model->getInvoicePackageOrderFranchise($invoiceRecords[0]->id, $value->package_id);
                $productItem += count($packageList);
                $value->product_item = count($packageList);
                $value->product_details = [];
                foreach ($packageList as $Pvalue) {
                    # code...
                    $productDetails = $this->Product_model->getProductDetails($Pvalue->product_id)[0];
                    $Pvalue->sku = $productDetails->product_sku;
                    // $finalObj = new stdClass();
                    // $finalObj->packageObj = $value;
                    // $finalObj->packageDetails = $Pvalue;
                    array_push($value->product_details, $Pvalue);
                }
            }
            $frnchise = $this->Product_model->checkDuplicateUserEntryFranchise('',$invoiceRecords[0]->franchise_id)[0];
          
            $data = [
                'currentPage' => '/wp-dashboard/franchise-settings/stocks/invoice-list',
                'pageName' => 'Return Invoice List',
                'invoice_details' => $invoiceRecords[0],
                'product_list' => $getOrderList,
                'package_list' => $getPackageList,
                'franchise'=>$frnchise
            ];
            return $this->adminView('refund-franchise-invoice', 'Products/Stocks/Admin/', $data);
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }
    
    public function buybackAttachmentDetails(){
        if ($this->isAdmin() || $this->isSuperAdmin()) {
            if($this->request->getGet('apply_filter') == 'true') { 
                $fromdate = $this->request->getGet('from_date').' 00:00:00';
                $todate = $this->request->getGet('to_date').' 23:59:59'; 
            }
            else{
            $month = date('m');
            $year = date('Y');
            $fromdate = date('Y-m-d', mktime(0, 0, 0, $month, 1,$year)).' 00:00:00';
            $todate = date('Y-m-t', mktime(0, 0, 0, $month, 28,$year)).' 23:59:59'; 
        }
        
        $buyback = $this->Product_model->getBuybackAttachment($fromdate,$todate);
        $allData = [];
        foreach($buyback as $buyvalue){
            $invoiceId = $buyvalue->invoice_id;
            $invoiceRecords = $this->Product_model->getInvoiceRecords($invoiceId);
            if(!empty($invoiceRecords)){
            $getFranchiseDetails = $this->Common_model->getKYCDetailsByUserIdFrachise($invoiceRecords[0]->franchise_id);
            $getMemberDetails    = $this->Common_model->getKYCDetailsByUserId($invoiceRecords[0]->mem_id);
            $getOrderList        = $this->Product_model->getOrderProductsInv($invoiceRecords[0]->order_id);
            $getPackageList      = $this->Product_model->getOrderPackagesInv($invoiceRecords[0]->order_id);
                
            
            
            $getFranchiseDetails[0]->city = $this->Common_model->getCityName($getFranchiseDetails[0]->city)[0]->city;
            $getFranchiseDetails[0]->state = $this->Common_model->getStateName($getFranchiseDetails[0]->state)[0]->name;
            $getMemberDetails[0]->city = $getMemberDetails[0]->city == '0' ? "" : $this->Common_model->getCityName($getMemberDetails[0]->city)[0]->city;
            $getMemberDetails[0]->state = $this->Common_model->getStateName($getMemberDetails[0]->state)[0]->name;
            $isRefundedStatus = false;
            $totalQty = $totalBV = $totalDP =0;
            foreach ($getOrderList as $key => $value) {
                # code...
                $orderList = $this->Product_model->getInvoiceProductOrder($invoiceRecords[0]->id, $value->product_id);
                $productDetails = $this->Product_model->getProductDetails($value->product_id)[0];
                $value->sku = $productDetails->product_sku;
                if($value->total_quantity != ''){
                            $totalDP += ((int)($value->total_quantity)-(int)($value->qty))*(float)($value->dp_amount);
                        }
                        else{
                            $totalDP += 0;
                        }
                if (!empty($orderList)) {
                    # code...
                    
                    $value->product_details = $orderList[0];
                }
                if ($value->status == '1') {
                    # code...
                    $isRefundedStatus = true;
                }
            }
            $productItem  = 0;
            foreach ($getPackageList as $key => $value) {
                # code...
                
                $packageList = $this->Product_model->getInvoicePackageOrder($invoiceRecords[0]->id, $value->package_id);
                $productItem += count($packageList);
                $value->package_name = $this->Product_model->getPackageDetails($value->package_id)[0]->package_name;
                $value->product_item = count($packageList);
                $value->product_details = [];
                if($value->total_quantity != ''){
                            $totalDP += ((int)($value->total_quantity)-(int)($value->qty))*(float)($value->dp_amount);
                        }
                        else{
                            $totalDP += 0;
                        }
                foreach ($packageList as $Pvalue) {
                    # code...
                    $productDetails = $this->Product_model->getProductDetails($Pvalue->product_id)[0];
                    $Pvalue->sku = $productDetails->product_sku;
                    // $finalObj = new stdClass();
                    // $finalObj->packageObj = $value;
                    // $finalObj->packageDetails = $Pvalue;
                    array_push($value->product_details, $Pvalue);
                }
                if ($value->status == '1') {
                    # code...
                    $isRefundedStatus = true;
                }
            }
            // echo "<pre>";
            // print_r ($getPackageList);
            // echo "<br>================================</br>";
            // print_r ($getFranchiseDetails);
            // echo "<br>================================</br>";
            // print_r ($getMemberDetails);
            // echo "<br>================PACKAGE================</br>";
            // print_r ($getPackageList);
            // // echo "<br>================ORDER================</br>";
            // print_r ($getOrderList);

            // echo "</pre>";
            // exit;
            $invoiceRecords[0]->totalDPr = $totalDP;
            $data = [
                'invoiceRecords' => $invoiceRecords[0],
                'memeber_details' => $getMemberDetails[0],
                'franchise_details' => $getFranchiseDetails[0],
                'product_order' => $getOrderList,
                'productItem' => $productItem,
                'package_list' => $getPackageList,
                'current_invoice' => true,
                'refund_status' => $isRefundedStatus,
                'buyBackDetails' => $buyvalue
            ];
            
            array_push($allData,$data);
        }
        }
        $finaldata = [
            'details'=>$allData
        ];
        return $this->adminView('buyback-attachment-admin', 'Products/Invoice/', $finaldata);
        }
        else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }
    
    
    public function AcceptBuyBackAttachment($id='',$inv=''){
        if ($this->isSuperAdmin() || $this->isAdmin()) {
            $invRec = $this->Product_model->getBuyBackDetails($inv);
            if(!empty($invRec)){
                $date = date('Y-m-d H:i:s');
                $this->Product_model->updateBuyBackDetails($id,'2','',$date);
                $this->setSessionNotification('wp_page',true, 'success', 'The operation has been updated successfully.');
                return $this->redirectToUrl('wp-dashboard/reports/buyback-attchment-details');
            }
            $this->setSessionNotification('wp_page',true, 'error', 'The operation has faild!.');
                return $this->redirectToUrl('reports/buyback-attchment-details');
        }
    }
    
    public function RejectBuyBackAttachment(){
        if ($this->isSuperAdmin() || $this->isAdmin()) {
            $id = $this->request->getPost('id');
            $inv = $this->request->getPost('inv');
            $msg  = $this->request->getPost('cancellation_message');
            
            $invRec = $this->Product_model->getBuyBackDetails($inv);
            if(!empty($invRec)){
                $date = date('Y-m-d H:i:s');
                $this->Product_model->updateBuyBackDetails($id,'0',$msg,$date);
                $this->setSessionNotification('wp_page',true, 'success', 'The operation has been Cancell successfully.');
                return $this->redirectToUrl('wp-dashboard/reports/buyback-attchment-details');
            }
            $this->setSessionNotification('wp_page',true, 'error', 'The operation has faild!.');
                return $this->redirectToUrl('reports/buyback-attchment-details');
        }
    }

    public function refundReportsAdmin() {
        if ($this->isSuperAdmin() || $this->isAdmin()) {
            if($this->request->getGet('apply_filter') == 'true') { 
                $fromDate = $this->request->getGet('from_date');
                $toDate = $this->request->getGet('to_date');
                $category = $this->request->getGet('category');
                $select_franchise = $this->request->getGet('select_franchise');
                
                if ($toDate < $fromDate) {
                    # code...
                    if($this->request->getGet('refund_type') == 'Buyback'){
                        $this->setSessionNotification('wp_page', true, 'error', 'Invalid Date Range is Given in Input');
                        return $this->redirectToUrl('/wp-dashboard/reports/refund-reports?refund_type=Buyback');
                    }
                    $this->setSessionNotification('wp_page', true, 'error', 'Invalid Date Range is Given in Input');
                    return $this->redirectToUrl('/wp-dashboard/reports/refund-reports');
                } 
                
                
                else{
                    if( $select_franchise !=''){
                        $loadReports = $this->Product_model->getInvoiceRecordsFilterByDate('3','', $fromDate, $toDate,$category,$select_franchise);
                    }
                    
                    else{
                        $loadReports = $this->Product_model->getInvoiceRecordsFilterByDate('3','', $fromDate, $toDate);
                    }
                }
                
                
                
            }else{
            $loadReports = $this->Product_model->getInvoiceRecords('', '3');
            }
            foreach ($loadReports as $key => $value) {
                # code...
               $value->franchise_name = $this->Common_model->getFranchiseDetailsId($value->franchise_id)[0]->user_name;
                $value->totalQty = 0;
                $value->totalBV = 0.00;
                $value->totalDP = 0.00;
                $productList;
                $packageList;
                if($this->request->getGet('apply_filter') == 'true' && $category == 'Product'){
                    $productList = $this->Product_model->getOrderProductsInv($value->order_id);
                }
                else if($this->request->getGet('apply_filter') == 'true' && $category == 'Package'){
                   $packageList = $this->Product_model->getOrderPackagesInv($value->order_id);
                }
                
                else if($this->request->getGet('apply_filter') == 'true' && $category == 'All'){
                   $packageList = $this->Product_model->getOrderPackagesInv($value->order_id);
                   $productList = $this->Product_model->getOrderProductsInv($value->order_id);
                }
                else{
                    $packageList = $this->Product_model->getOrderPackagesInv($value->order_id);
                    $productList = $this->Product_model->getOrderProductsInv($value->order_id);
                }
            
            
                if (!empty($productList)) {
                    # code...
                    foreach ($productList as $Pkey => $Pvalue) {
                        # code...
                        
                        if($Pvalue->total_quantity != ''){
                            $value->totalQty += (int)($Pvalue->total_quantity)-(int)($Pvalue->qty);
                            $value->totalBV += ((int)($Pvalue->total_quantity)-(int)($Pvalue->qty))*(float)($Pvalue->bv_amout);
                            $value->totalDP += ((int)($Pvalue->total_quantity)-(int)($Pvalue->qty))*(float)($Pvalue->dp_amount);
                            // $value->totalBV += (float)($Pvalue->bv_amout)*(int)($Pvalue->qty);
                        }
                        else{
                            $value->totalQty += (int)($Pvalue->qty);
                            $value->totalBV += (float)($Pvalue->bv_amout)*(int)($Pvalue->qty);
                            $value->totalDP += ((int)($Pvalue->total_quantity)-(int)($Pvalue->qty))*(float)($Pvalue->dp_amount);
                        }
                    }
                    
                }

                if (!empty($packageList)) {
                    # code...
                    
                    foreach ($packageList as $cKey => $Cvalue) {
                        
                        # code...
                        if($Cvalue->total_quantity != ''){
                            $value->totalQty += (int)($Cvalue->total_quantity)-(int)($Cvalue->qty);
                            $value->totalBV += ((int)($Cvalue->total_quantity)-(int)($Cvalue->qty))*(float)($Cvalue->bv_amout);
                            $value->totalDP += ((int)($Cvalue->total_quantity)-(int)($Cvalue->qty))*(float)($Cvalue->dp_amount);
                        }
                        else{
                            $value->totalQty += (int)($Cvalue->qty);
                            $value->totalBV += (float)($Cvalue->bv_amout)*(int)($Cvalue->qty);
                            $value->totalDP += ((int)($Cvalue->total_quantity)-(int)($Cvalue->qty))*(float)($Cvalue->dp_amount);
                        }
                    }
                }
                
                
                $memberDetails = $this->Common_model->getCustomMemSelectDataByUserId($value->mem_id, 'current_level,mobile_no, full_name, members_id,tracking_id ')[0];
                $memberDetailsSponser = $this->Common_model->getCustomMemSelectDataByMemId($memberDetails->tracking_id, 'full_name')[0];
                $value->current_level = $memberDetails->current_level;
                $value->full_name = $memberDetails->full_name;
                $value->members_id = $memberDetails->members_id;
                $value->mobile_no = $memberDetails->mobile_no;
                $value->sponser_id = $memberDetails->tracking_id;
                $value->sponser_name = $memberDetailsSponser->full_name;
                $value->net_amount = $value->net_amount + $value->total_cgst + $value->total_sgst + $value->total_igst;
                
            }
            
             $filterNormalRefund = array_filter($loadReports, function ($value) {
                $invoiceDate = date('Y-m-d', strtotime($value->created_date));
                $refundDate = date('Y-m-d', strtotime($value->refunded_date));
                $after90Days = date('Y-m-d', strtotime($invoiceDate . " + 19 days"));
                $currentDate = date('Y-m-d');
                if ($refundDate < $after90Days) {
                    return $value;
                }
            });
            
            $filterBuybackRefund = array_filter($loadReports, function ($value) {
                $invoiceDate = date('Y-m-d', strtotime($value->created_date));
                $refundDate = date('Y-m-d', strtotime($value->refunded_date));
                $after90Days = date('Y-m-d', strtotime($invoiceDate . " + 19 days"));
                $currentDate = date('Y-m-d');
                if ($refundDate > $after90Days) {
                    return $value;
                }
            });
            $franchise_list = $this->Common_model->getAllFranchiseName();
            
            $data = [
                'currentPage'         => '/wp-dashboard/invoice/Refund',
                'pageName'            => 'Refund',
                'transation_list'     => $loadReports,
                'normal_refund'       => array_values($filterNormalRefund),
                'buyback_refund'       => array_values($filterBuybackRefund),
                'franchise_list'       => $franchise_list
            ];
            
            if($this->request->getGet('refund_type') == 'Buyback'){
                return $this->adminView('admin-buyback-refund', 'Products/Orders/', $data);
            }
            return $this->adminView('admin-order-refund', 'Products/Orders/', $data);
        
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }
    
    public function invoiceReportsAdmin() {
        if ($this->isSuperAdmin() || $this->isAdmin()) {
            if($this->request->getGet('apply_filter') == 'true') { 
                $fromDate = $this->request->getGet('from_date');
                $toDate = $this->request->getGet('to_date').' 23:59:59';
                $category = $this->request->getGet('category');
                $select_franchise = $this->request->getGet('select_franchise');
                
                if ($toDate < $fromDate) {
                    # code...
                    $this->setSessionNotification('wp_page', true, 'error', 'Invalid Date Range is Given in Input');
                    return $this->redirectToUrl('/wp-dashboard/invoice/reports');
                } 
                
                else{
                    if( $select_franchise !=''){
                        $loadReports = $this->Product_model->getInvoiceRecordsFilterByDate('23','', $fromDate, $toDate,$category,$select_franchise);
                    }
                    
                    else{
                        $loadReports = $this->Product_model->getInvoiceRecordsFilterByDate('23','', $fromDate, $toDate);
                    }
                }
                
                
                
            } else {
                $loadReports = $this->Product_model->getInvoiceRecords('', '23', '');
            }
            // echo "<pre>";
            // print_r($loadReports);
            // echo "/<pre>";exit;
            foreach ($loadReports as $key => $value) {
                # code...
                $value->franchise_name = $this->Common_model->getFranchiseDetailsId($value->franchise_id)[0]->user_name;
                $value->totalQty = 0;
                $value->totalBV = 0;
                $value->totalDP = 0;
                $productList;
                $packageList;
                if($this->request->getGet('apply_filter') == 'true' && $category == 'Product'){
                    $productList = $this->Product_model->getOrderProductsInv($value->order_id);
                }
                else if($this->request->getGet('apply_filter') == 'true' && $category == 'Package'){
                   $packageList = $this->Product_model->getOrderPackagesInv($value->order_id);
                }
                
                else if($this->request->getGet('apply_filter') == 'true' && $category == 'All'){
                   $packageList = $this->Product_model->getOrderPackagesInv($value->order_id);
                   $productList = $this->Product_model->getOrderProductsInv($value->order_id);
                }
                else{
                    $packageList = $this->Product_model->getOrderPackagesInv($value->order_id);
                    $productList = $this->Product_model->getOrderProductsInv($value->order_id);
                }
                if (!empty($productList)) {
                    # code...
                    foreach ($productList as $Pkey => $Pvalue) {
                        # code...
                     if($Pvalue->total_quantity != ''){
                            $value->totalQty += (int)($Pvalue->qty);
                            $value->totalBV += (int)($Pvalue->qty)*(float)($Pvalue->bv_amout);
                            $value->totalDP += (float)($Pvalue->dp_amount)*(int)($Pvalue->qty);
                        }
                        else{
                            $value->totalQty += (int)($Pvalue->qty);
                            $value->totalBV += (float)($Pvalue->bv_amout)*(int)($Pvalue->qty);
                            $value->totalDP += (float)($Pvalue->dp_amount)*(int)($Pvalue->qty);
                        }
                    }
                    
                    
                }

                if (!empty($packageList)) {
                    # code...
                    foreach ($packageList as $cKey => $Cvalue) {
                        # code...
                        
                        if($Cvalue->total_quantity != ''){
                            
                            $value->totalQty += (int)($Cvalue->qty);
                            $value->totalBV += (int)($Cvalue->qty)*(float)($Cvalue->bv_amout);
                            $value->totalDP += (int)($Cvalue->qty)*(float)($Cvalue->dp_amount);
                        }
                        else{
                            $value->totalQty += (int)($Cvalue->qty);
                            $value->totalBV += (float)($Cvalue->bv_amout)*(int)($Cvalue->qty);
                            $value->totalDP += (float)($Cvalue->dp_amount)*(int)($Cvalue->qty);
                        }
                        
                    }
                    
                }
                $memberDetails = $this->Common_model->getCustomMemSelectDataByUserId($value->mem_id,'current_level, full_name, members_id, ')[0];
                $value->current_level = $memberDetails->current_level;
                $value->full_name = $memberDetails->full_name;
                $value->members_id = $memberDetails->members_id;
                $value->net_amount = $value->net_amount + $value->total_cgst + $value->total_sgst + $value->total_igst;
            }
            
            $franchise_list = $this->Common_model->getAllFranchiseName();
            
            $data = [
                'currentPage'         => '/wp-dashboard/invoice/reports',
                'pageName'            => 'Reports',
                'transation_list'     => $loadReports,
                'franchise_list'      => $franchise_list
            ];
            
            return $this->adminView('admin-order-reports', 'Products/Orders/', $data);
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function adminViewInvoiceReport($invoiceId = '') {
        if ($this->isSuperAdmin() || $this->isAdmin()) {
            $invoiceRecords = $this->Product_model->getInvoiceRecords($invoiceId);
            if (empty($invoiceRecords)) {
                # code...
                $this->setSessionNotification('wp_page', false, 'error', 'Invalid details given. please try again later.');
                return $this->redirectToUrl('wp-dashboard/reports/refund-reports');
            }
            $getFranchiseDetails = $this->Common_model->getKYCDetailsByUserIdFrachise($invoiceRecords[0]->franchise_id);
            $getMemberDetails    = $this->Common_model->getKYCDetailsByUserId($invoiceRecords[0]->mem_id);
            $getOrderList        = $this->Product_model->getOrderProducts($invoiceRecords[0]->order_id);
            $getPackageList      = $this->Product_model->getOrderPackages($invoiceRecords[0]->order_id);
            $getFranchiseDetails[0]->city = $this->Common_model->getCityName($getFranchiseDetails[0]->city)[0]->city;
            $getFranchiseDetails[0]->state = $this->Common_model->getStateName($getFranchiseDetails[0]->state)[0]->name;
            $getMemberDetails[0]->city = $this->Common_model->getCityName($getMemberDetails[0]->city)[0]->city;
            $getMemberDetails[0]->state = $this->Common_model->getStateName($getMemberDetails[0]->state)[0]->name;
            $isRefundedStatus = false;
            foreach ($getOrderList as $key => $value) {
                # code...
                $orderList = $this->Product_model->getInvoiceProductOrder($invoiceRecords[0]->id, $value->product_id);
                $productDetails = $this->Product_model->getProductDetails($value->product_id)[0];
                $value->sku = $productDetails->product_sku;
                if (!empty($orderList)) {
                    # code...

                    $value->product_details = $orderList[0];
                }
                if ($value->status == '1') {
                    # code...
                    $isRefundedStatus = true;
                }
            }
            $productItem  = 0;
            foreach ($getPackageList as $key => $value) {
                # code...
                $packageList = $this->Product_model->getInvoicePackageOrder($invoiceRecords[0]->id, $value->package_id);
                $productItem += count($packageList);
                $value->package_name = $this->Product_model->getPackageDetails($value->package_id)[0]->package_name;
                $value->product_item = count($packageList);
                $value->product_details = [];
                foreach ($packageList as $Pvalue) {
                    # code...
                    $productDetails = $this->Product_model->getProductDetails($Pvalue->product_id)[0];
                    $Pvalue->sku = $productDetails->product_sku;
                    // $finalObj = new stdClass();
                    // $finalObj->packageObj = $value;
                    // $finalObj->packageDetails = $Pvalue;
                    array_push($value->product_details, $Pvalue);
                }
                if ($value->status == '1') {
                    # code...
                    $isRefundedStatus = true;
                }
            }
            // echo "<pre>";
            // print_r ($invoiceRecords);
            // echo "<br>================================</br>";
            // print_r ($getFranchiseDetails);
            // echo "<br>================================</br>";
            // print_r ($getMemberDetails);
            // echo "<br>================PACKAGE================</br>";
            // print_r ($getPackageList);
            // echo "<br>================ORDER================</br>";
            // print_r ($getOrderList);

            // echo "</pre>";
            // exit;
            $data = [
                'invoiceRecords' => $invoiceRecords[0],
                'memeber_details' => $getMemberDetails[0],
                'franchise_details' => $getFranchiseDetails[0],
                'product_order' => $getOrderList,
                'productItem' => $productItem,
                'package_list' => $getPackageList,
                'current_invoice' => true,
                'refund_status' => $isRefundedStatus,
            ];
            if ($this->request->getGet('buyback_note') == 'true') {
                $data['buyBackDetails'] = $this->Product_model->getBuyBackDetails($invoiceRecords[0]->id);
                return  $this->adminView('buy-back-return-attachment', 'Products/Invoice', $data);
            }
            return  $this->adminView('admin-invoice-details-return', 'Products/Invoice', $data);
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function loadProductNotice() {
        if ($this->isSuperAdmin() || $this->isAdmin()) {
            $loadReports = $this->Product_model->getProductNotice();
            $productList =  [];
            if (empty($loadReports)) {
                $productList = $this->Product_model->getAllProductList();
            } else {
                $productList = $this->Product_model->getAllProductList();
                $productList = array_filter($productList, function($value) use ($loadReports) {
                    if (array_search($value->id, array_column($loadReports, 'product_id')) == '') {
                        # code...
                        return $value;
                    }
                });
            }
            $data = [
                'currentPage'         => '/wp-dashboard/invoice/Refund',
                'pageName'            => 'Refund',
                'transation_list'     => $loadReports,
                'product_list'       => array_values($productList)
            ];
              
            return $this->adminView('admin-product-notice', 'Products/Orders/', $data);
        
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function loadPackageNotice() {
        if ($this->isSuperAdmin() || $this->isAdmin()) {
            $loadReports = $this->Product_model->loadPackageNotice();
            $productList =  [];
            if (empty($loadReports)) {
                $productList = $this->Product_model->getAllPackageList();
            } else {
                $productList = $this->Product_model->getAllPackageList();
                $productList = array_filter($productList, function ($value) use ($loadReports) {
                    if (array_search($value->id, array_column($loadReports, 'package_id')) == '') {
                        # code...
                        return $value;
                    }
                });
            }
            $data = [
                'currentPage'         => '/wp-dashboard/invoice/Refund',
                'pageName'            => 'Refund',
                'transation_list'     => $loadReports,
                'package_list'        => array_values($productList)
            ];
            return $this->adminView('admin-package-notice', 'Products/Orders/', $data);
        
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function saveProductNotice() {
        if ($this->isSuperAdmin() || $this->isAdmin()) {
            $data = [
                'user_id' => $this->getSessions('user_id'),
                'product_id' => $this->request->getPost('product_name'),
                'price' => $this->request->getPost('price'),
                'qty' => $this->request->getPost('qty'),
                'is_gst' => $this->request->getPost('p_gst') ? $this->request->getPost('p_gst') : 0,
                'created_date' => date('Y-m-d H:i:s')
            ];
            $this->Product_model->saveProductNotice($data);
            $this->setSessionNotification('wp_page', true, 'success', 'Recorded save successfully');
            return $this->redirectToUrl('wp-dashboard/purchase-notice/product-notice');            
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function savePackageNotice()
    {
        if ($this->isSuperAdmin() || $this->isAdmin()) {
            $data = [
                'user_id' => $this->getSessions('user_id'),
                'package_id' => $this->request->getPost('product_name'),
                'price' => $this->request->getPost('price'),
                'qty' => $this->request->getPost('qty'),
                'is_gst' => $this->request->getPost('p_gst') ? $this->request->getPost('p_gst') : 0,
                'created_date' => date('Y-m-d H:i:s')
            ];
            $this->Product_model->savePackageNotice($data);
            $this->setSessionNotification('wp_page', true, 'success', 'Recorded save successfully');
            return $this->redirectToUrl('wp-dashboard/purchase-notice/package-notice');
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function removeProductNotice($id = '') {
        if ($this->isSuperAdmin() || $this->isAdmin()) {
            $this->Product_model->removeProductNotice($id);
            $this->setSessionNotification('wp_page', true, 'success', 'Recorded removed successfully');
            return $this->redirectToUrl('wp-dashboard/purchase-notice/product-notice');            
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    public function removePackageNotice($id = '') {
        if ($this->isSuperAdmin() || $this->isAdmin()) {
            $this->Product_model->removePackageNotice($id);
            $this->setSessionNotification('wp_page', true, 'success', 'Recorded removed successfully');
            return $this->redirectToUrl('wp-dashboard/purchase-notice/package-notice');            
        } else {
            $this->setSessionExpiredNotification();
            return $this->redirectToUrl('home');
        }
    }

    private function generateIncomeRecordsBySpecialClub($memberObj, $orderObject = null) {
        if (($memberObj->current_level == '6' || $memberObj->current_level == '9') && $memberObj->total_bv >= 25) {
            # code...
            $orderDetails = $this->Common_model->getMemberOrderByMemIdCurrentMonth($memberObj->user_id);
            $thisMonth = 0.00;
            $thisMonth = array_reduce($orderDetails, function ($carry, $item) {
                return $carry + $item->total_bv;
            }, 0.00);
            $extraBonous = $this->Common_model->getExtraBonous($memberObj->user_id);
            $extraBonousAmount = !empty($extraBonous) ? $extraBonous[0]->added_bv : 0.00;
            $totalBv = $thisMonth + $orderObject->total_bv + $extraBonousAmount;
            $isBVCountAgreed = ($totalBv) >= $this->specialLineClubCTO()->bv_required ? true : false;
            $getCurrentSpecialClubIncome = $this->Common_model->getCurrentSpecialClubIncomeById($memberObj->user_id);
            if ($isBVCountAgreed && empty($getCurrentSpecialClubIncome)) {
                # code...
                $myDownlines = $this->loadMyDownLinesTotalByRemoveLevel($memberObj->members_id, $memberObj->tracking_id, ['6', '9']);
                if ($myDownlines >= 2000) {
                    # code...
                    $insertData = [
                        'user_id' => $memberObj->user_id,
                        'current_level' => $memberObj->current_level,
                        'created_date' => date('Y-m-d H:i:s'),
                    ];
                    $this->Common_model->inserSpecialShipClubDetails($insertData);
                }
            }
        }
    }

    private function generateIncomeRecordsByTargetClub($memberObj= null, $orderObject = null) {
        if ($memberObj->total_bv >= 25) {
            # code...
            // Target Club Sliver Leader Promotional bonous entries
            if ((int)($memberObj->current_level) == 4) {
                # code...
                $orderDetails = $this->Common_model->getMemberOrderByMemIdCurrentMonth($memberObj->user_id);
                $thisMonth = 0.00;
                $thisMonth = array_reduce($orderDetails, function ($carry, $item) {
                    return $carry + $item->total_bv;
                }, 0.00);
                $extraBonous = $this->Common_model->getExtraBonous($memberObj->user_id);
                $extraBonousAmount = !empty($extraBonous) ? $extraBonous[0]->added_bv : 0.00;
                $totalBv = $thisMonth + $orderObject->total_bv + $extraBonousAmount;
                $isBVCountAgreed = ($totalBv) >= $this->targetClubCTO($memberObj->current_level)->bv_required ? true : false;
                $getCurrentTargetClubIncome = $this->Common_model->getCurrentTargetClubIncomeById($memberObj->user_id);
                if ($isBVCountAgreed && empty($getCurrentTargetClubIncome)) {
                    $myDirects = $memberObj->tracking_id === $memberObj->members_id ? $this->Report_model->getDirectUser($memberObj->tracking_id) : $this->Report_model->getDirectUser($memberObj->members_id, true);
                    if (!empty($myDirects)) {
                        # code...
                        $totalDownlineCount = 0;
                        $nonSilverDirects = array_values(array_filter($myDirects, fn ($value) => $value->current_level != '4'));
                        $silverLeaderDirect =  array_values(array_filter($myDirects, fn ($value) => $value->current_level == '4'));
                        $totalSilverCount = count($silverLeaderDirect);
                        if ($totalSilverCount >= 2) {
                            # code...
                            $downLineBvCount = [];
                            foreach ($silverLeaderDirect as $key => $value) {
                                # code..
                                $thisMonthBv = $this->getCurrentMonthFilterOrder($value->user_id);
                                $isValidIncome = ($thisMonthBv) >= $this->targetClubCTO($value->current_level)->bv_required ? true : false;
                                $myDownlineObj = $this->getAllMyDownLineFilterLevel($value->members_id, $value->tracking_id, '4');
                                $myDownlineObj->downLineBV += $thisMonthBv;
                                if ($value->total_bv >= 25 && $isValidIncome && (count($myDownlineObj->myfilterDownline) == 0 || $myDownlineObj->downLineBV >= 50)) {
                                    # code...
                                    if ($myDownlineObj->downLineBV >= 50) {
                                        # code...
                                        array_push($downLineBvCount, $myDownlineObj->downLineBV);
                                    }
                                } elseif (count($myDownlineObj->myfilterDownline) > 0 ) {
                                    # code...
                                    if ($myDownlineObj->downLineBV >= 50) {
                                        # code...
                                        array_push($downLineBvCount, $myDownlineObj->downLineBV);
                                    }
                                }
                            }
                            // $downLineBvCount = array_unique($downLineBvCount);
                            // // rsort : sorts an array in reverse order (highest to lowest).
                            // rsort($downLineBvCount);
                            $sortTotalCountBv = array_reduce($downLineBvCount, function ($carry, $item) {
                                return $item >= 50 ?  $carry + $item :  $carry;
                            }, 0.00);
                            if ($sortTotalCountBv >= 300) {
                                # code...
                                $insertData  = [
                                    'user_id'    => $orderObject->mem_id,
                                    'current_level'   => $memberObj->current_level,
                                    'club_promot_type' => '1',
                                    'created_date' => date('Y-m-d H:i:s')
                                ];
                                $this->Common_model->inserTargetClubDetails($insertData);
                            }
                        } elseif ($totalSilverCount == 1) {
                            # code...
                            $totalSingleLineBv = 0;
                            foreach ($nonSilverDirects as $key => $value) {
                                # code...
                                $selfBV = $this->getCurrentMonthFilterOrder($value->user_id);
                                $downlineBV = $this->loadMyDownLinesTotalByRemoveLevel($value->members_id, $value->tracking_id, []);
                                $totalSingleLineBv += $selfBV + $downlineBV;
                            }
                            if ($totalSingleLineBv >= 25) {
                                # code...
                                $silverLeaderDirect = $silverLeaderDirect[0];
                                $thisMonthBv = $this->getCurrentMonthFilterOrder($silverLeaderDirect->user_id);
                                $isValidIncome = ($thisMonthBv) >= $this->targetClubCTO($silverLeaderDirect->current_level)->bv_required ? true : false;
                                $myDownlineObj = $this->getAllMyDownLineFilterLevel($silverLeaderDirect->members_id, $silverLeaderDirect->tracking_id, '4');
                                $myDownlineObj->downLineBV += $thisMonthBv;
                                if ($silverLeaderDirect->total_bv >= 25 && $isValidIncome) {
                                    if($myDownlineObj->downLineBV >= 150) {
                                        $insertData  = [
                                            'user_id'    => $orderObject->mem_id,
                                            'current_level'   => $memberObj->current_level,
                                            'created_date' => date('Y-m-d H:i:s'),
                                            'club_promot_type' => '1',
                                        ];
                                        $this->Common_model->inserTargetClubDetails($insertData);
                                    }
                                }
                            }
                        } else {
                            $totalSingleLineBv = 0;
                            foreach ($nonSilverDirects as $key => $value) {
                                # code...
                                $selfBV = $this->getCurrentMonthFilterOrder($value->user_id);
                                $downlineBV = $this->loadMyDownLinesTotalByRemoveLevel($value->members_id, $value->tracking_id, ['4']);
                                $totalSingleLineBv += $selfBV + $downlineBV;
                            }
                            if ($totalSingleLineBv >= 250) {
                                # code...
                                $insertData  = [
                                    'user_id'    => $orderObject->mem_id,
                                    'current_level'   => $memberObj->current_level,
                                    'created_date' => date('Y-m-d H:i:s'),
                                    'club_promot_type' => '1',
                                ];
                                $this->Common_model->inserTargetClubDetails($insertData);
                            }
                        }
                    }
                }
            }

            // Target Club Golden Leader Promotional bonous entries
            if ((int)($memberObj->current_level) == 5) {
                $orderDetails = $this->Common_model->getMemberOrderByMemIdCurrentMonth($memberObj->user_id);
                $thisMonth = 0.00;
                $thisMonth = array_reduce($orderDetails, function ($carry, $item) {
                    return $carry + $item->total_bv;
                }, 0.00);
                $extraBonous = $this->Common_model->getExtraBonous($memberObj->user_id);
                $extraBonousAmount = !empty($extraBonous) ? $extraBonous[0]->added_bv : 0.00;
                $totalBv = $thisMonth + $orderObject->total_bv + $extraBonousAmount;
                $isBVCountAgreed = ($totalBv) >= $this->targetClubCTO($memberObj->current_level)->bv_required ? true : false;
                $getCurrentTargetClubIncome = $this->Common_model->getCurrentTargetClubIncomeById($memberObj->user_id);
                if ($isBVCountAgreed && empty($getCurrentTargetClubIncome)) {
                    $myDirects = $memberObj->tracking_id === $memberObj->members_id ? $this->Report_model->getDirectUser($memberObj->tracking_id) : $this->Report_model->getDirectUser($memberObj->members_id, true);
                    if (!empty($myDirects)) {
                        # code...
                        $totalDownlineCount = 0;
                        $nonSilverDirects = array_values(array_filter($myDirects, fn($value) => $value->current_level != '5'));
                        $silverLeaderDirect =  array_values(array_filter($myDirects, fn($value) => $value->current_level == '5'));
                        $totalSilverCount = count($silverLeaderDirect);
                        if ($totalSilverCount >= 2) {
                            # code...
                            $downLineBvCount = [];
                            foreach ($silverLeaderDirect as $key => $value) {
                                # code..
                                $thisMonthBv = $this->getCurrentMonthFilterOrder($value->user_id);
                                $isValidIncome = ($thisMonthBv) >= $this->targetClubCTO($value->current_level)->bv_required ? true : false;
                                $myDownlineObj = $this->getAllMyDownLineFilterLevel($value->members_id, $value->tracking_id, '5');
                                $myDownlineObj->downLineBV += $thisMonthBv;
                                if ($value->total_bv >= 25 && $isValidIncome && (count($myDownlineObj->myfilterDownline) == 0 || $myDownlineObj->downLineBV >= 75)) {
                                    # code...
                                    if ($myDownlineObj->downLineBV >= 75) {
                                        # code...
                                        array_push($downLineBvCount, $myDownlineObj->downLineBV);
                                    }
                                } elseif (count($myDownlineObj->myfilterDownline) > 0) {
                                    # code...
                                    if ($myDownlineObj->downLineBV >= 75) {
                                        # code...
                                        array_push($downLineBvCount, $myDownlineObj->downLineBV);
                                    }
                                }
                            }
                            // $downLineBvCount = array_unique($downLineBvCount);
                            // // rsort : sorts an array in reverse order (highest to lowest).
                            // rsort($downLineBvCount);
                            $sortTotalCountBv = array_reduce($downLineBvCount, function ($carry, $item) {
                                return $item >= 75 ?  $carry + $item :  $carry;
                            }, 0.00);
                            if ($sortTotalCountBv >= 750) {
                                # code...
                                $insertData  = [
                                    'user_id'    => $orderObject->mem_id,
                                    'current_level'   => $memberObj->current_level,
                                    'created_date' => date('Y-m-d H:i:s'),
                                    'club_promot_type' => '2',
                                ];
                                $this->Common_model->inserTargetClubDetails($insertData);
                            }
                        } elseif ($totalSilverCount == 1) {
                            # code...
                            $totalSingleLineBv = 0;
                            foreach ($nonSilverDirects as $key => $value) {
                                # code...
                                $selfBV = $this->getCurrentMonthFilterOrder($value->user_id);
                                $downlineBV = $this->loadMyDownLinesTotalByRemoveLevel($value->members_id, $value->tracking_id, []);
                                $totalSingleLineBv += $selfBV + $downlineBV;
                            }
                            if ($totalSingleLineBv >= 50) {
                                # code...
                                $silverLeaderDirect = $silverLeaderDirect[0];
                                $thisMonthBv = $this->getCurrentMonthFilterOrder($silverLeaderDirect->user_id);
                                $isValidIncome = ($thisMonthBv) >= $this->targetClubCTO($silverLeaderDirect->current_level)->bv_required ? true : false;
                                $myDownlineObj = $this->getAllMyDownLineFilterLevel($silverLeaderDirect->members_id, $silverLeaderDirect->tracking_id, '5');
                                $myDownlineObj->downLineBV += $thisMonthBv;
                                if ($silverLeaderDirect->total_bv >= 25 && $isValidIncome) {
                                    if ($myDownlineObj->downLineBV >= 375) {
                                        $insertData  = [
                                            'user_id'    => $orderObject->mem_id,
                                            'current_level'   => $memberObj->current_level,
                                            'created_date' => date('Y-m-d H:i:s'),
                                            'club_promot_type' => '2',
                                        ];
                                        $this->Common_model->inserTargetClubDetails($insertData);
                                    }
                                }
                            }
                        } else {
                            $totalSingleLineBv = 0;
                            foreach ($nonSilverDirects as $key => $value) {
                                # code...
                                $selfBV = $this->getCurrentMonthFilterOrder($value->user_id);
                                $downlineBV = $this->loadMyDownLinesTotalByRemoveLevel($value->members_id, $value->tracking_id, ['5']);
                                $totalSingleLineBv += $selfBV + $downlineBV;
                            }
                            
                            // echo "<pre>";
                            // print_r($nonSilverDirects);
                            // print_r ($totalSingleLineBv);
                            // echo "</pre>";
                            // exit;
                            if ($totalSingleLineBv >= 500) {
                                # code...
                                $insertData  = [
                                    'user_id'    => $orderObject->mem_id,
                                    'current_level'   => $memberObj->current_level,
                                    'created_date' => date('Y-m-d H:i:s'),
                                    'club_promot_type' => '2',
                                ];
                                $this->Common_model->inserTargetClubDetails($insertData);
                            }
                        }
                    }
                }
            }

            //  Target club Platinum promotional entries
            if ((int)($this->convertSameLevelMembers($memberObj->current_level)) == 6) {
                $orderDetails = $this->Common_model->getMemberOrderByMemIdCurrentMonth($memberObj->user_id);
                $thisMonth = 0.00;
                $thisMonth = array_reduce($orderDetails, function ($carry, $item) {
                    return $carry + $item->total_bv;
                }, 0.00);
                $extraBonous = $this->Common_model->getExtraBonous($memberObj->user_id);
                $extraBonousAmount = !empty($extraBonous) ? $extraBonous[0]->added_bv : 0.00;
                $totalBv = $thisMonth + $orderObject->total_bv + $extraBonousAmount;
                $isBVCountAgreed = ($totalBv) >= $this->targetClubCTO($memberObj->current_level)->bv_required ? true : false;
                $getCurrentTargetClubIncome = $this->Common_model->getCurrentTargetClubIncomeById($memberObj->user_id);
                if ($isBVCountAgreed && empty($getCurrentTargetClubIncome)) {
                    $myDirects = $memberObj->tracking_id === $memberObj->members_id ? $this->Report_model->getDirectUser($memberObj->tracking_id) : $this->Report_model->getDirectUser($memberObj->members_id, true);
                    if (!empty($myDirects)) {
                        # code...
                        $nonSilverDirects = array_values(array_filter($myDirects, fn ($value) => $this->convertSameLevelMembers($value->current_level) != '6'));
                        $silverLeaderDirect =  array_values(array_filter($myDirects, fn ($value) => $this->convertSameLevelMembers($value->current_level) == '6'));
                        $totalSilverCount = count($silverLeaderDirect);
                        if ($totalSilverCount >= 2) {
                            # code...
                            $downLineBvCount = [];
                            foreach ($silverLeaderDirect as $key => $value) {
                                # code..
                                $thisMonthBv = $this->getCurrentMonthFilterOrder($value->user_id);
                                $isValidIncome = ($thisMonthBv) >= $this->targetClubCTO($value->current_level)->bv_required ? true : false;
                                $myDownlineObj = $this->getAllMyDownLineFilterLevel($value->members_id, $value->tracking_id, '6');
                                $myDownlineObj->downLineBV += $thisMonthBv;
                                if ($value->total_bv >= 25 && $isValidIncome && (count($myDownlineObj->myfilterDownline) == 0 || $myDownlineObj->downLineBV >= 750)) {
                                    # code...
                                    if ($myDownlineObj->downLineBV >= 750) {
                                        # code...
                                        array_push($downLineBvCount, $myDownlineObj->downLineBV);
                                    }
                                } elseif (count($myDownlineObj->myfilterDownline) > 0) {
                                    # code...
                                    if ($myDownlineObj->downLineBV >= 750) {
                                        # code...
                                        array_push($downLineBvCount, $myDownlineObj->downLineBV);
                                    }
                                }
                            }
                            // $downLineBvCount = array_unique($downLineBvCount);
                            // // rsort : sorts an array in reverse order (highest to lowest).
                            // rsort($downLineBvCount);
                            $sortTotalCountBv = array_reduce($downLineBvCount, function ($carry, $item) {
                                return $item >= 750 ?  $carry + $item :  $carry;
                            }, 0.00);
                            if ($sortTotalCountBv >= 2000) {
                                # code...
                                $insertData  = [
                                    'user_id'    => $orderObject->mem_id,
                                    'current_level'   => $memberObj->current_level,
                                    'created_date' => date('Y-m-d H:i:s'),
                                    'club_promot_type' => '3',
                                ];
                                $this->Common_model->inserTargetClubDetails($insertData);
                            }
                        } elseif ($totalSilverCount == 1) {
                            # code...
                            $totalSingleLineBv = 0;
                            foreach ($nonSilverDirects as $key => $value) {
                                # code...
                                $selfBV = $this->getCurrentMonthFilterOrder($value->user_id);
                                $downlineBV = $this->loadMyDownLinesTotalByRemoveLevel($value->members_id, $value->tracking_id, []);
                                $totalSingleLineBv += $selfBV + $downlineBV;
                            }
                            if ($totalSingleLineBv >= 75) {
                                # code...
                                $silverLeaderDirect = $silverLeaderDirect[0];
                                $thisMonthBv = $this->getCurrentMonthFilterOrder($silverLeaderDirect->user_id);
                                $isValidIncome = ($thisMonthBv) >= $this->targetClubCTO($silverLeaderDirect->current_level)->bv_required ? true : false;
                                $myDownlineObj = $this->getAllMyDownLineFilterLevel($silverLeaderDirect->members_id, $silverLeaderDirect->tracking_id, '6');
                                $myDownlineObj->downLineBV += $thisMonthBv;
                                if ($silverLeaderDirect->total_bv >= 25 && $isValidIncome) {
                                    if ($myDownlineObj->downLineBV >= 1000) {
                                        $insertData  = [
                                            'user_id'    => $orderObject->mem_id,
                                            'current_level'   => $memberObj->current_level,
                                            'created_date' => date('Y-m-d H:i:s'),
                                            'club_promot_type' => '3',
                                        ];
                                        $this->Common_model->inserTargetClubDetails($insertData);
                                    }
                                }
                            }
                        } else {
                            $totalSingleLineBv = 0;
                            foreach ($nonSilverDirects as $key => $value) {
                                # code...
                                $selfBV = $this->getCurrentMonthFilterOrder($value->user_id);
                                $downlineBV = $this->loadMyDownLinesTotalByRemoveLevel($value->members_id, $value->tracking_id, ['6', '9']);
                                $totalSingleLineBv += $selfBV + $downlineBV;
                            }
                            if ($totalSingleLineBv >= 1250) {
                                # code...
                                $insertData  = [
                                    'user_id'    => $orderObject->mem_id,
                                    'current_level'   => $memberObj->current_level,
                                    'created_date' => date('Y-m-d H:i:s'),
                                    'club_promot_type' => '3',
                                ];
                                $this->Common_model->inserTargetClubDetails($insertData);
                            }
                        }
                    }
                }
            }

            // Target club Dynamic leader entries
            if ($this->convertSameLevelMembers($memberObj->current_level) ==  7) {
                # code...
                $orderDetails = $this->Common_model->getMemberOrderByMemIdCurrentMonth($memberObj->user_id);
                $thisMonth = 0.00;
                $thisMonth = array_reduce($orderDetails, function ($carry, $item) {
                    return $carry + $item->total_bv;
                }, 0.00);
                $extraBonous = $this->Common_model->getExtraBonous($memberObj->user_id);
                $extraBonousAmount = !empty($extraBonous) ? $extraBonous[0]->added_bv : 0.00;
                $totalBv = $thisMonth + $orderObject->total_bv + $extraBonousAmount;
                $isBVCountAgreed = ($totalBv) >= $this->targetClubCTO($memberObj->current_level)->bv_required ? true : false;
                $getCurrentTargetClubIncome = $this->Common_model->getCurrentTargetClubIncomeById($memberObj->user_id);
                if ($isBVCountAgreed && empty($getCurrentTargetClubIncome)) {
                    $myDirects = $memberObj->tracking_id === $memberObj->members_id ? $this->Report_model->getDirectUser($memberObj->tracking_id) : $this->Report_model->getDirectUser($memberObj->members_id, true);
                    if (!empty($myDirects)) {
                        # code...
                        $nonSilverDirects = array_values(array_filter($myDirects, fn ($value) => $this->convertSameLevelMembers($value->current_level) != '7'));
                        $silverLeaderDirect =  array_values(array_filter($myDirects, fn ($value) => $this->convertSameLevelMembers($value->current_level) == '7'));
                        $totalSilverCount = count($silverLeaderDirect);
                        // if ($totalSilverCount >= 3) {
                        //     $insertData  = [
                        //         'user_id'    => $orderObject->mem_id,
                        //         'current_level'   => $memberObj->current_level,
                        //         'created_date' => date('Y-m-d H:i:s')
                        //     ];
                        //     $this->Common_model->inserTargetClubDetails($insertData);
                        // } else
                        $downLineBvCount = [];
                        if ($totalSilverCount >= 2) {
                            # code...
                            foreach ($silverLeaderDirect as $key => $value) {
                                # code..
                                $thisMonthBv = $this->getCurrentMonthFilterOrder($value->user_id);
                                $isValidIncome = ($thisMonthBv) >= $this->targetClubCTO($value->current_level)->bv_required ? true : false;
                                $myDownlineObj = $this->getAllMyDownLineFilterLevel($value->members_id, $value->tracking_id, '7');
                                if ($value->total_bv >= 25 && $isValidIncome && (count($myDownlineObj->myfilterDownline) == 0 || $myDownlineObj->downLineBV >= 0)) {
                                    # code...
                                    if ($myDownlineObj->downLineBV >= 0) {
                                        # code...
                                        array_push($downLineBvCount, $myDownlineObj->downLineBV);
                                    }
                                } elseif (count($myDownlineObj->myfilterDownline) > 0) {
                                    # code...
                                    if ($myDownlineObj->downLineBV >= 0) {
                                        # code...
                                        array_push($downLineBvCount, $myDownlineObj->downLineBV);
                                    }
                                }
                            }
                            // $downLineBvCount = array_unique($downLineBvCount);
                            // // rsort : sorts an array in reverse order (highest to lowest).
                            // rsort($downLineBvCount);
                            $sortTotalCountBv = array_reduce($downLineBvCount, function ($carry, $item) {
                                return $item >= 0 ?  $carry + $item :  $carry;
                            }, 0.00);
                            if ($sortTotalCountBv >= 1000) {
                                # code...
                                $insertData  = [
                                        'user_id'    => $orderObject->mem_id,
                                        'current_level'   => $memberObj->current_level,
                                        'created_date' => date('Y-m-d H:i:s'),
                                        'club_promot_type' => '4',
                                    ];
                                $this->Common_model->inserTargetClubDetails($insertData);
                            }
                        } elseif ($totalSilverCount == 1) {
                            # code...
                            $totalSingleLineBv = 0;
                            $silverLeaderDirect = $silverLeaderDirect[0];
                            $thisMonthBv = $this->getCurrentMonthFilterOrder($silverLeaderDirect->user_id);
                            $isValidIncome = ($thisMonthBv) >= $this->targetClubCTO($silverLeaderDirect->current_level)->bv_required ? true : false;
                            $myDownlineObj = $this->getAllMyDownLineFilterLevel($silverLeaderDirect->members_id, $silverLeaderDirect->tracking_id, '7');
                            $isMoreDynamicFound = false;
                            $moreDynamicLeaderBVCount = [];
                            foreach ($nonSilverDirects as $key => $value) {
                                $downlineDynamicCount = $this->getAllMyDownLineFilterLevel($value->members_id, $value->tracking_id, '7');
                                # code...
                                if (count($downlineDynamicCount->myfilterDownline) > 0) {
                                    # code...
                                    $isMoreDynamicFound = true;
                                    array_push($moreDynamicLeaderBVCount, $downlineDynamicCount->downLineBV);
                                }
                                $selfBV = $this->getCurrentMonthFilterOrder($value->user_id);
                                $downlineBV = $this->loadMyDownLinesTotalByRemoveLevel($value->members_id, $value->tracking_id, []);
                                $totalSingleLineBv += $selfBV + $downlineBV;
                            }
                            if ($isMoreDynamicFound) {
                                # code...
                                $sortTotalCountBv = array_reduce($downLineBvCount, function ($carry, $item) {
                                    return $item >= 0 ?  $carry + $item :  $carry;
                                }, 0.00) + $myDownlineObj->downLineBV;
                                if ($sortTotalCountBv >= 1000) {
                                    # code...
                                    $insertData  = [
                                        'user_id'    => $orderObject->mem_id,
                                        'current_level'   => $memberObj->current_level,
                                        'created_date' => date('Y-m-d H:i:s'),
                                        'club_promot_type' => '4',
                                    ];
                                    $this->Common_model->inserTargetClubDetails($insertData);
                                }
                            } else {
                                $totalSingleLineBv += $myDownlineObj->downLineBV;
                                if ($totalSingleLineBv >= 2500) {
                                    # code...
                                    $insertData  = [
                                        'user_id'    => $orderObject->mem_id,
                                        'current_level'   => $memberObj->current_level,
                                        'created_date' => date('Y-m-d H:i:s'),
                                        'club_promot_type' => '4',
                                    ];
                                    $this->Common_model->inserTargetClubDetails($insertData);
                                }
                            }
                        } else {
                            $totalSingleLineBv = 0;
                            $countMyDownlineDynamic = 0;
                            $moreDynamicLeaderBVCount = [];
                            foreach ($nonSilverDirects as $key => $value) {
                                # code...
                                $downlineDynamicCount = $this->getAllMyDownLineFilterLevel($value->members_id, $value->tracking_id, '7');
                                # code...
                                if (count($downlineDynamicCount->myfilterDownline) > 0) {
                                    # code...
                                    $countMyDownlineDynamic += 1;
                                    array_push($moreDynamicLeaderBVCount, $downlineDynamicCount->downLineBV);
                                } else {
                                    $selfBV = $this->getCurrentMonthFilterOrder($value->user_id);
                                    $downlineBV = $this->loadMyDownLinesTotalByRemoveLevel($value->members_id, $value->tracking_id, ['6', '9']);
                                    $totalSingleLineBv += $selfBV + $downlineBV;
                                }
                            }
                            if ($countMyDownlineDynamic == 1) {
                                # code... 
                                if ($totalSingleLineBv >= 2500) {
                                    # code...
                                    $insertData  = [
                                        'user_id'    => $orderObject->mem_id,
                                        'current_level'   => $memberObj->current_level,
                                        'created_date' => date('Y-m-d H:i:s'),
                                        'club_promot_type' => '4',
                                    ];
                                    $this->Common_model->inserTargetClubDetails($insertData);
                                } else {
                                    foreach ($moreDynamicLeaderBVCount as $key => $value) {
                                        # code...
                                        if ($value >= 2500) {
                                            # code...
                                            $insertData  = [
                                                'user_id'    => $orderObject->mem_id,
                                                'current_level'   => $memberObj->current_level,
                                                'created_date' => date('Y-m-d H:i:s'),
                                                'club_promot_type' => '4',
                                            ];
                                            $this->Common_model->inserTargetClubDetails($insertData);
                                            break;
                                        }
                                    }
                                }
                            } elseif($countMyDownlineDynamic >= 2) {
                                $sortTotalCountBv = array_reduce($moreDynamicLeaderBVCount, function ($carry, $item) {
                                    return $item >= 0 ?  $carry + $item :  $carry;
                                }, 0.00);
                                if ($sortTotalCountBv >= 1000) {
                                    # code...
                                    $insertData  = [
                                        'user_id'    => $orderObject->mem_id,
                                        'current_level'   => $memberObj->current_level,
                                        'created_date' => date('Y-m-d H:i:s'),
                                        'club_promot_type' => '4',
                                    ];
                                    $this->Common_model->inserTargetClubDetails($insertData);
                                }
                            } else {
                                if ($totalSingleLineBv >= 4500) {
                                    # code...
                                    $insertData  = [
                                        'user_id'    => $orderObject->mem_id,
                                        'current_level'   => $memberObj->current_level,
                                        'created_date' => date('Y-m-d H:i:s'),
                                        'club_promot_type' => '4',
                                    ];
                                    $this->Common_model->inserTargetClubDetails($insertData);
                                }
                            }
                        }
                    }
                }
            }

            // Target Club Super Dynamic Leader Entries
            if ($this->convertSameLevelMembers($memberObj->current_level) ==  8) {
                # code...
                $orderDetails = $this->Common_model->getMemberOrderByMemIdCurrentMonth($memberObj->user_id);
                $thisMonth = 0.00;
                $thisMonth = array_reduce($orderDetails, function ($carry, $item) {
                    return $carry + $item->total_bv;
                }, 0.00);
                $extraBonous = $this->Common_model->getExtraBonous($memberObj->user_id);
                $extraBonousAmount = !empty($extraBonous) ? $extraBonous[0]->added_bv : 0.00;
                $totalBv = $thisMonth + $orderObject->total_bv + $extraBonousAmount;
                $isBVCountAgreed = ($totalBv) >= $this->targetClubCTO($memberObj->current_level)->bv_required ? true : false;
                $getCurrentTargetClubIncome = $this->Common_model->getCurrentTargetClubIncomeById($memberObj->user_id);
                if ($isBVCountAgreed && empty($getCurrentTargetClubIncome)) {
                    $myDirects = $memberObj->tracking_id === $memberObj->members_id ? $this->Report_model->getDirectUser($memberObj->tracking_id) : $this->Report_model->getDirectUser($memberObj->members_id, true);
                    if (!empty($myDirects)) {
                        # code...
                        $nonSilverDirects = array_values(array_filter($myDirects, fn ($value) => $this->convertSameLevelMembers($value->current_level) != '8'));
                        $silverLeaderDirect =  array_values(array_filter($myDirects, fn ($value) => $this->convertSameLevelMembers($value->current_level) == '8'));
                        $totalSilverCount = count($silverLeaderDirect);
                        $isMoreDynamicFound = false;
                        $moreDynamicLeaderBVCount = [];
                        $downLineBvCount = [];
                        $totalSingleLineBv = 0;
                        if ($totalSilverCount >= 3) {
                            $insertData  = [
                                'user_id'    => $orderObject->mem_id,
                                'current_level'   => $memberObj->current_level,
                                'created_date' => date('Y-m-d H:i:s'),
                                'club_promot_type' => '5',
                            ];
                            $this->Common_model->inserTargetClubDetails($insertData);
                        } elseif ($totalSilverCount == 2) {
                            # code...
                            foreach ($nonSilverDirects as $key => $value) {
                                $downlineDynamicCount = $this->getAllMyDownLineFilterLevel($value->members_id, $value->tracking_id, '8');
                                # code...
                                if (count($downlineDynamicCount->myfilterDownline) > 0) {
                                    # code...
                                    $insertData  = [
                                        'user_id'    => $orderObject->mem_id,
                                        'current_level'   => $memberObj->current_level,
                                        'created_date' => date('Y-m-d H:i:s'),
                                        'club_promot_type' => '5',
                                    ];
                                    $this->Common_model->inserTargetClubDetails($insertData);
                                    $isMoreDynamicFound = true;
                                    break;
                                } else {
                                    $selfBV = $this->getCurrentMonthFilterOrder($value->user_id);
                                    $downlineBV = $this->loadMyDownLinesTotalByRemoveLevel($value->members_id, $value->tracking_id, []);
                                    $totalSingleLineBv += $selfBV + $downlineBV;
                                }
                            }
                            if (!$isMoreDynamicFound) {
                                # code...
                                if ($totalSingleLineBv >= 3750) {
                                    # code...
                                    $insertData  = [
                                        'user_id'    => $orderObject->mem_id,
                                        'current_level'   => $memberObj->current_level,
                                        'created_date' => date('Y-m-d H:i:s'),
                                        'club_promot_type' => '5',
                                    ];
                                    $this->Common_model->inserTargetClubDetails($insertData);
                                } else {
                                    foreach ($silverLeaderDirect as $key => $value) {
                                        # code..
                                        $thisMonthBv = $this->getCurrentMonthFilterOrder($value->user_id);
                                        $isValidIncome = ($thisMonthBv) >= $this->targetClubCTO($value->current_level)->bv_required ? true : false;
                                        $myDownlineObj = $this->getAllMyDownLineFilterLevel($value->members_id, $value->tracking_id, '8');
                                        if ($value->total_bv >= 25 && $isValidIncome && (count($myDownlineObj->myfilterDownline) == 0)) {
                                            # code...
                                            if ($myDownlineObj->downLineBV >= 0) {
                                                # code...
                                                array_push($downLineBvCount, $myDownlineObj->downLineBV);
                                            }
                                        } elseif (count($myDownlineObj->myfilterDownline) > 0) {
                                            # code...
                                            if ($myDownlineObj->downLineBV >= 0) {
                                                # code...
                                                array_push($downLineBvCount, $myDownlineObj->downLineBV);
                                            }
                                        }
                                    }
                                    // $downLineBvCount = array_unique($downLineBvCount);
                                    // // rsort : sorts an array in reverse order (highest to lowest).
                                    // rsort($downLineBvCount);
                                    $sortTotalCountBv = array_reduce($downLineBvCount, function ($carry, $item) {
                                            return $item >= 0 ?  $carry + $item :  $carry;
                                        }, 0.00);
                                    if ($sortTotalCountBv >= 3750) {
                                        # code...
                                        $insertData  = [
                                            'user_id'    => $orderObject->mem_id,
                                            'current_level'   => $memberObj->current_level,
                                            'created_date' => date('Y-m-d H:i:s'),
                                            'club_promot_type' => '5',
                                        ];
                                        $this->Common_model->inserTargetClubDetails($insertData);
                                    }

                                }
                            }

                        } elseif ($totalSilverCount == 1) {
                            # code...
                            $totalSingleLineBv = 0;
                            $silverLeaderDirect = $silverLeaderDirect[0];
                            $thisMonthBv = $this->getCurrentMonthFilterOrder($silverLeaderDirect->user_id);
                            $isValidIncome = ($thisMonthBv) >= $this->targetClubCTO($silverLeaderDirect->current_level)->bv_required ? true : false;
                            $isMoreDynamicFound = false;
                            $countDynamicCount = 0;
                            $moreDynamicLeaderBVCount = [];
                            foreach ($nonSilverDirects as $key => $value) {
                                $downlineDynamicCount = $this->getAllMyDownLineFilterLevel($value->members_id, $value->tracking_id, '8');
                                # code...
                                if (count($downlineDynamicCount->myfilterDownline) > 0) {
                                    # code...
                                    $countDynamicCount += 1;
                                    $isMoreDynamicFound = true;
                                    array_push($moreDynamicLeaderBVCount, $downlineDynamicCount->downLineBV);
                                } else {
                                    $selfBV = $this->getCurrentMonthFilterOrder($value->user_id);
                                    $downlineBV = $this->loadMyDownLinesTotalByRemoveLevel($value->members_id, $value->tracking_id, []);
                                    $totalSingleLineBv += $selfBV + $downlineBV;
                                }
                            }
                            if ($countDynamicCount >= 2) {
                                # code...
                                if ($isValidIncome || $countDynamicCount > 2 ) {
                                    # code...
                                    $insertData  = [
                                        'user_id'    => $orderObject->mem_id,
                                        'current_level'   => $memberObj->current_level,
                                        'created_date' => date('Y-m-d H:i:s'),
                                        'club_promot_type' => '5',
                                    ];
                                    $this->Common_model->inserTargetClubDetails($insertData);
                                }
                            } elseif ($countDynamicCount >= 1)  {
                                foreach ($moreDynamicLeaderBVCount as $key => $value) {
                                    # code...
                                    if (($isValidIncome && $value >= 3750) || $value >=7500) {
                                        # code...
                                        $insertData  = [
                                            'user_id'    => $orderObject->mem_id,
                                            'current_level'   => $memberObj->current_level,
                                            'created_date' => date('Y-m-d H:i:s'),
                                            'club_promot_type' => '5',
                                        ];
                                        $this->Common_model->inserTargetClubDetails($insertData);
                                        break;
                                    }
                                }
                            } else {
                                if ($isValidIncome && $totalSingleLineBv >= 7500 ) {
                                    # code...
                                    $insertData  = [
                                        'user_id'    => $orderObject->mem_id,
                                        'current_level'   => $memberObj->current_level,
                                        'created_date' => date('Y-m-d H:i:s'),
                                        'club_promot_type' => '5',
                                    ];
                                    $this->Common_model->inserTargetClubDetails($insertData);
                                }
                            }
                        } else {
                            $totalSingleLineBv = 0;
                            $countMyDownlineDynamic = 0;
                            $moreDynamicLeaderBVCount = [];
                            foreach ($nonSilverDirects as $key => $value) {
                                # code...
                                $downlineDynamicCount = $this->getAllMyDownLineFilterLevel($value->members_id, $value->tracking_id, '7');
                                # code...
                                if (count($downlineDynamicCount->myfilterDownline) > 0) {
                                    # code...
                                    $countMyDownlineDynamic += 1;
                                    array_push($moreDynamicLeaderBVCount, $downlineDynamicCount->downLineBV);
                                } else {
                                    $selfBV = $this->getCurrentMonthFilterOrder($value->user_id);
                                    $downlineBV = $this->loadMyDownLinesTotalByRemoveLevel($value->members_id, $value->tracking_id, ['6', '9']);
                                    $totalSingleLineBv += $selfBV + $downlineBV;
                                }
                            }
                            if ($countMyDownlineDynamic >= 3) {
                                # code... 
                                $insertData  = [
                                    'user_id'    => $orderObject->mem_id,
                                    'current_level'   => $memberObj->current_level,
                                    'created_date' => date('Y-m-d H:i:s'),
                                    'club_promot_type' => '5',
                                ];
                                $this->Common_model->inserTargetClubDetails($insertData);
                            } elseif ($countMyDownlineDynamic >= 2) {
                                $sumBv = 0;
                                if($totalSingleLineBv >= 3750) {
                                    $insertData  = [
                                        'user_id'    => $orderObject->mem_id,
                                        'current_level'   => $memberObj->current_level,
                                        'created_date' => date('Y-m-d H:i:s'),
                                        'club_promot_type' => '5',
                                    ];
                                    $this->Common_model->inserTargetClubDetails($insertData);
                                }
                            } elseif ($countMyDownlineDynamic >= 1) {
                                if ($totalSingleLineBv >= 7500) {
                                    # code...
                                    $insertData  = [
                                        'user_id'    => $orderObject->mem_id,
                                        'current_level'   => $memberObj->current_level,
                                        'created_date' => date('Y-m-d H:i:s'),
                                        'club_promot_type' => '5',
                                    ];
                                    $this->Common_model->inserTargetClubDetails($insertData);
                                }
                            } else {
                                if ($totalSingleLineBv >= 12500) {
                                    # code...
                                    $insertData  = [
                                        'user_id'    => $orderObject->mem_id,
                                        'current_level'   => $memberObj->current_level,
                                        'created_date' => date('Y-m-d H:i:s'),
                                        'club_promot_type' => '5',
                                    ];
                                    $this->Common_model->inserTargetClubDetails($insertData);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    private function loadMyDownLinesTotalByRemoveLevel($memberId = '', $memberTrackingId= "", $levelArr = []) {
        $myDirects = $memberTrackingId === $memberId ? $this->Report_model->getDirectUser($memberTrackingId) : $this->Report_model->getDirectUser($memberId, true);
        $allMembers = $this->Report_model->getAllNonDirectMem($memberTrackingId);
        $myDownlineBv = 0;
        foreach ($allMembers as $key => $value) {
            # code...
            foreach ($myDirects as $cKey => $cValue) {
                # code...
                if ($value->tracking_id === $cValue->members_id) {
                    # code...
                    array_push($myDirects, $value);
                }
            }
        }


        foreach ($myDirects as $cKey => $cValue) {
            $levelStatus =  (!empty($levelArr)) && is_numeric($this->Search($cValue->current_level, $levelArr)) ? true : false;
            if (!$levelStatus) {
                # code...
                $orderDetails = $this->Common_model->getMemberOrderByMemIdCurrentMonth($cValue->user_id);
                $totalBV = 0.00;
                $totalBV = array_reduce($orderDetails, function ($carry, $item) {
                    return $carry + $item->total_bv;
                }, 0.00);
                $myDownlineBv += $totalBV;
            }
        }
        return $myDownlineBv;
    }

    private function countArrayKey($array, $key) {
        return count(array_keys($array, $key));
    }

    function currentMemberLevelSearch($id = '', $array = [])
    {
        foreach ($array as $element) {
            if ($id == $element->temp_level) {
                return $element;
            }
        }

        return false;
    }
    

    private function getCurrentMonthFilterOrder($userId = '') {
        $orderDetails = $this->Common_model->getMemberOrderByMemIdCurrentMonth($userId);
        $thisMonth = 0.00;
        $extraBonous = $this->Common_model->getExtraBonous($userId);
        $extraBonousAmount = !empty($extraBonous) ? $extraBonous[0]->added_bv : 0.00;
        $thisMonth = array_reduce($orderDetails, function ($carry, $item) {
            return $carry + $item->total_bv;
        }, 0.00);
        return $thisMonth + $extraBonousAmount;
    }


    private function getAllMyDownLineFilterLevel($memId, $trackingId, $requiredLeader = '')
    {
        $myDirects = $directCountLine = $trackingId === $memId ? $this->Report_model->getDirectUser($trackingId) : $this->Report_model->getDirectUser($memId, true);
        $allMembers = $this->Report_model->getAllNonDirectMem($trackingId);
        $directDownLineBV = 0;
        foreach ($allMembers as $key => $value) {
            # code...
            foreach ($myDirects as $cKey => $cValue) {
                # code...
                if ($value->tracking_id === $cValue->members_id) {
                    # code..
                    array_push($myDirects,  $value);
                }
            }
        }
        $requestedSpecificLeaderArr = array_values(array_filter($myDirects, fn($value) => $this->convertSameLevelMembers($value->current_level) == $this->convertSameLevelMembers($requiredLeader)));
        if (count($requestedSpecificLeaderArr) == 0) {
            # code...
            foreach ($myDirects as $cKey => $cValue) {
                # code...
                $orderDetails = $this->Common_model->getMemberOrderByMemIdCurrentMonth($cValue->user_id);
                $totalBV = 0.00;
                $totalBV = array_reduce($orderDetails, function ($carry, $item) {
                    return $carry + $item->total_bv;
                }, 0.00);
                $directDownLineBV += $totalBV;
            }
        } else {
            foreach ($requestedSpecificLeaderArr as $key => $value) {
                # code...
                $thisMonthBv = $this->getCurrentMonthFilterOrder($value->user_id);
                $isValidIncome = ($thisMonthBv) >= $this->targetClubCTO($value->current_level)->bv_required ? true : false;
                if ($value->total_bv >= 25 && $isValidIncome) {
                    # code...
                    $directDownLineBV += $this->getDirectsDownlineByFilterOrderTotal($value->members_id, $value->tracking_id, $requiredLeader);
                    if ($this->convertSameLevelMembers($value->current_level) != 7 || $this->convertSameLevelMembers($value->current_level) != 8) {
                        # code...
                        $directDownLineBV += $thisMonthBv;
                    }
                }
            }
        }
        $returnObj = new stdClass();
        $returnObj->myDirects = $myDirects;
        $returnObj->myfilterDownline = $requestedSpecificLeaderArr;
        $returnObj->downLineBV = $directDownLineBV;
        return $returnObj;
    }

    private function getDirectsDownlineByFilterOrderTotal($memId, $trackingId, $currentLevel = '') {
        $myDirects = $directCountLine = $trackingId === $memId ? $this->Report_model->getDirectUser($trackingId) : $this->Report_model->getDirectUser($memId, true);
        $allMembers = $this->Report_model->getAllNonDirectMem($trackingId);
        $myDirects = array_values(array_filter($myDirects, fn($value) => $this->convertSameLevelMembers($value->current_level) != $this->convertSameLevelMembers($currentLevel)));
        $myDownlineBv = 0.00;
        foreach ($allMembers as $key => $value) {
            # code...
            foreach ($myDirects as $cKey => $cValue) {
                # code...
                if ($value->tracking_id === $cValue->members_id) {
                    # code..
                    if ($this->convertSameLevelMembers($cValue->currentLvl) != $currentLevel) {
                        array_push($myDirects,  $value);
                    }
                }
            }
        }
        foreach ($myDirects as $cKey => $cValue) {
            $orderDetails = $this->Common_model->getMemberOrderByMemIdCurrentMonth($cValue->user_id);
            $totalBV = 0.00;
            $totalBV = array_reduce($orderDetails, function ($carry, $item) {
                return $carry + $item->total_bv;
            }, 0.00);
            $myDownlineBv += $totalBV;
        }
        return $myDownlineBv;
    }
}
