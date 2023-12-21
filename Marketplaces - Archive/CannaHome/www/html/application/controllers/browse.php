<?php

/**
 * Class Index
 * The index controller
 */
class Browse extends Controller
{
	/**
	* Construct this object by extending the basic Controller class
	*/
	function __construct()
	{
		//parent::__construct('main', FALSE, TRUE);
	}

	/**
	* Handles what happens when user moves to URL/index/index, which is the same like URL/index or in this
	* case even URL (without any controller/action) as this is the default controller-action when user gives no input.
	*/ 
	function index()
	{
		$args = func_get_args();
		call_user_func_array(array($this, 'listing'), $args);
	}
	 
	function listing($listing_b36, $content = 'main', $page = 1, $browseModel = false){
		parent::__construct('main', TRUE, TRUE);
		$listing_id = NXS::getDecimal($listing_b36);
		
		if($this->User->IsAdmin && isset($_GET['id'])){
			echo $listing_id;
			die;
		}
		
		if( !($listing_id && is_numeric($listing_id) && $listing_id > 0) ){
			header('Location: ' . URL);
			die;
		}
		
		$page = is_numeric($page) && $page > 0 ? $page : 1;
		
		$this->view->listingID = $listing_id;
		$this->view->listingB36 = htmlspecialchars($listing_b36);
		
		if (!$browseModel)
			$browseModel = $this->loadModel('Browse');
		
		switch($content){
			case 'comments':
				if(
					list(
						$this->view->listingName,
						$this->view->vendorAlias,
						$this->view->commentCount,
						$this->view->comments
					) = $browseModel->fetchComments(
						$listing_id,
						$page
					)
				){
					$this->view->pageNumber = $page;
					$this->view->SiteName = $this->view->listingName . ': ' . $this->view->SiteName;
					
					$this->view->breadcrumb = [
						$this->view->vendorAlias => [
							'URL' => '/v/' . $this->view->vendorAlias . '/',
							'icon' => Icon::getClass('STAR', true)
						],
						'Listings' => [
							'URL' => '/v/' . $this->view->vendorAlias . '/listings/'
						],
						$this->view->listingName => [
							'URL' => '/i/' . htmlspecialchars($listing_b36) . '/',
							'shrink' => true
						],
						'Ratings' => false
					];
					
					return $this->view->render('browse/listing_comments');
				}
			break;
			default:
				if($this->view->listing = $browseModel->fetchListing($listing_id)){
					$this->view->vendorAlias = $this->view->listing['vendor']['alias'];
					$this->view->SiteName = $this->view->listing['listing']['name'] . ': ' . $this->view->SiteName;
					
					$this->view->breadcrumb = [
						$this->view->vendorAlias => [
							'URL' => '/v/' . $this->view->vendorAlias . '/',
							'icon' => Icon::getClass('STAR', true)
						],
						'Listings' => [
							'URL' => '/v/' . $this->view->vendorAlias . '/listings/'
						],
						$this->view->listing['listing']['name'] => [
							'shrink' => true
						]
					];
					
					return $this->view->render('browse/listing');
				}
		}
		
		// Default Outcome
		header('Location: ' . URL . 'error/');
		die;
	}
	
	function upload($filename){
		parent::__construct(FALSE, FALSE, FALSE, TRUE);
		$uploadModel = $this->loadModel('Upload');
		
		if ($imageBlob = $uploadModel->getDatabaseImage($filename)){
			header("Content-Type: image/" . strtolower(end(explode('.', $filename))));
			echo $imageBlob;
		} else
			header("HTTP/1.0 404 Not Found");
		die;
	}
	
	function user($vendor_alias = false, $content = 'main', $page = 1, $browseModel = false){
		parent::__construct('main', TRUE, TRUE, TRUE);
		if( !($vendor_alias) ){
			header('Location: '.URL);
			die;
		}
		
		if ($content == 'listings'){
			$args = array_merge(
				array($vendor_alias),
				array_slice(
					func_get_args(),
					2
				)
			);
			return call_user_func_array(
				array($this, 'vendor_listings'),
				$args
			);
		}
		
		if (!$browseModel && $content !== 'listings')
			$browseModel = $this->loadModel('Browse');
		
		$page = is_numeric($page) && $page > 0 ? $page : 1;
		
		// Custom Profile CSS
		if( $customCSS = $browseModel->fetchUserProfileCSS($vendor_alias) )
			$this->view->inlineStylesheet .= ' ' . $customCSS;
		
		switch($content){
			case 'comments':
				$this->view->pageNumber = $page;
				if(
					list(
						$this->view->vendorAlias,
						$this->view->commentCount,
						$this->view->comments
					) = $browseModel->fetchVendorComments(
						$vendor_alias,
						$page
					)
				){
					$this->view->SiteName = $this->view->vendorAlias . ': ' . $this->view->SiteName;
					$this->view->breadcrumb = [
						$this->view->vendorAlias => [
							'URL' => '/v/' . $this->view->vendorAlias . '/',
							'icon' => Icon::getClass('STAR', true)
						],
						'Ratings' => false
					];
					
					$this->view->ratingBreakdown = $this->view->ratingAttributeBreakdown = false;
					if (
						!$this->db->forum &&
						$page == 1
					){
						$this->view->ratingBreakdown = $browseModel->getUserRatingBreakdown($vendor_alias);
						$this->view->ratingAttributeBreakdown = $browseModel->getUserRatingAttributeBreakdown($vendor_alias);
					}
					
					return $this->view->render('browse/vendor_comments');
				}
			break;
			default:
				$this->view->vendor = $browseModel->fetchVendor($vendor_alias);
				
				$this->view->vendorAlias = $this->view->vendor['alias'];
				$this->view->SiteName = $this->view->vendorAlias . ': ' . $this->view->SiteName;
				return $this->view->render('browse/vendor');
		}
		
		header('Location: ' . URL . 'error/');
		die;	
	}
	
	function vendor_listings(
		$vendorAlias,
		$category = FALSE,
		$sort = 'rating',
		$page = 1
	){
		parent::__construct('main', TRUE, TRUE);
		
		// Preliminary Validation
		$page = (!is_numeric($page) || $page < 1) ? 1 : $page;
		//$category = (!is_numeric($category) || $category < 0) ? 0 : $category;
		switch($sort){
			case 'popular':
			case 'price_asc':
			case 'price_desc':
			case 'price_m_asc':
			case 'price_m_desc':
			case 'price_v_asc':
			case 'price_v_desc':
			case 'name_asc':
			case 'name_desc':
			case 'id_asc':
			case 'id_desc':
			break;
			default:
				$sort = 'rating';
		}
		
		$category = $category == 'all' || $category=='index' ? false : $category;
		
		$this->view->categoryAlias = htmlspecialchars($category) ?: 'all';
		
		$catalogModel = $this->loadModel('Catalog');
		
		$this->view->sortMode = $sort;
		$this->view->vendorAlias = $catalogModel->getUserAlias($vendorAlias);
		$this->view->filterPreferences = $this->User->Attributes['Preferences']['CatalogFilter'];
		
		$this->view->shippingDestinations = $catalogModel->fetchShippingDestinations(
			$vendorAlias,
			$shipsToPreference
		);
		
		if( $category && !is_numeric($category) )
			$category = $catalogModel->getCategoryID($category);
		
		list(
			$this->view->listingCategories,
			$this->view->activeListingCategories,
			$visibleListingCategories
		) = $catalogModel->fetchListingCategories(
			$category,
			$vendorAlias,
			false,
			$shipsToPreference ? false : -1,
			null
		);
		
		$this->view->categoryID = $category ? $category : 'all';
		
		list(
			$this->view->listingCount,
			$this->view->trueListingCount,
			$this->view->listings
		) = $catalogModel->fetchListings(
			$visibleListingCategories,
			$page,
			$sort,
			FALSE,
			$vendorAlias,
			$shipsToPreference ? false : -1,
			null
		);
		
		$this->view->pageNumber = ceil($this->view->listingCount/LISTINGS_PER_PAGE) >= $page ? $page : 1;
		
		$this->view->SiteName = $this->view->vendorAlias . ': ' . $this->view->SiteName;
		
		$this->view->breadcrumb = [
			$this->view->vendorAlias => [
				'URL' => '/v/' . $this->view->vendorAlias . '/',
				'icon' => Icon::getClass('STAR', true)
			],
			'Listings' => false
		];
		
		$this->view->render('catalog/listings');
	}
	
	function storefront($prefix){
		parent::__construct('main', FALSE, TRUE);
		
		$browseModel = $this->loadModel('Browse');
		
		$args = array_slice(func_get_args(), 1);
		
		if( $vendor_alias = $browseModel->findVendorAlias($prefix) ){
			
			$params =	array_merge(
				array($vendor_alias),
				$args
			);
			
			$params = array_replace(array(
				false,
				'main',
				'rating',
				1,
				$browseModel
			), $params);
			
			call_user_func_array( array($this, 'user'), $params);
			
		} else {
			
			header('Location: ' . URL);
			die;
			
		}
		
	}
	
	public function is_vendor_page($page){
		
		switch($page){
			case 'listings':
			case 'comments':
			case 'main':
			case 'listing':
				return true;
			break;
			default:
				header('Location: ' . URL);
				die;
		}
		
	}
	
	function vendor(){
		// ALIAS FOR USER
		$args = func_get_args();
		call_user_func_array(array($this, 'user'), $args);
	}
}
