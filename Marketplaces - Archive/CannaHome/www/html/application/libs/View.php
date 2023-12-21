<?php

/**
 * Class View
 *
 * Provides the methods all views will have
 */
class View {
	public $SiteName;
	
	public function __construct(
		$template,
		$db = false,
		$isTester = false
	){
		$this->template = $template;
		$this->AccessPrefix = htmlspecialchars($db->prefix);
		
		$this->incognitoMode = $this->AccessPrefix == INCOGNITO_ACCESS_PREFIX;
		
		$this->javascripts = [];
		if($db){
			$this->viewStartTime = false;
			if ($isTester)
				$this->viewStartTime = time();
			
			$this->SiteName = $this->incognitoMode ? INCOGNITO_SITE_TITLE : $db->site_name;
			list(
				$this->SiteName_Short,
				$this->SiteStylesheetPath,
				$this->SiteFaviconPath,
				$this->SiteLogo,
				$this->SiteSlogan,
				$this->SitePGP,
				$this->SiteHeaderRegex['Pattern'],
				$this->SiteHeaderRegex['Substitution'],
				$this->SitePrimaryColor,
				$this->ForumURL,
				$this->MainURL,
				$this->ServerIdentifier
			) = $db->getSiteInfo(
				'SiteName_Short',
				'StylesheetPath',
				'FaviconPath',
				'Logo',
				'Slogan',
				'PGP',
				'HeaderRegex_Pattern',
				'HeaderRegex_Substitution',
				'PrimaryColor',
				'ForumLink',
				'MainLink',
				($isTester ? 'ServerIdentifier' : false)
			);
			//$this->UserAvatar = NXS::getPictureVariant($userAvatar, IMAGE_THUMBNAIL_SUFFIX);
		}
		$this->currentPath = $this->getCurrentPath();
	}
	
	public function preRender(){
		return TRUE;
	}
	
	public function getCSRFToken(){
		if (empty($_SESSION['csrf_token']))
			$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
		
		return $_SESSION['csrf_token'];
	}
	
	/**
	 * simply includes (=shows) the view. this is done from the controller. In the controller, you usually say
	 * $this->view->render('help/index'); to show (in this example) the view index.php in the folder help.
	 * Usually the Class and the method are the same like the view, but sometimes you need to show different views.
	 * @param string $filename Path of the to-be-rendered view, usually folder/file(.php)
	 * @param boolean $render_without_header_and_footer Optional: Set this to true if you don't want to include header and footer
	 */
	public function render($filename, $template = null){
		$this->preRender();
		$template = $template !== null ? $template: $this->template;
		
		$this->serverLoadStats = false;
		if ($this->viewStartTime)
			$this->serverLoadStats =
				'Server ' . $this->ServerIdentifier . '; ' .
				(time() - $this->viewStartTime) . 's';
		
		$folder = explode('/', $filename); $folder = $folder[0];
		if (
			$template &&
			file_exists(
				VIEWS_PATH . '_templates/' . $template
			)
		){
			require VIEWS_PATH . '_templates/' . $template . '/header.php';
			require VIEWS_PATH . $filename . '.php';
			require VIEWS_PATH . '_templates/' . $template . '/footer.php';
		} else {
			require VIEWS_PATH . $filename . '.php';
		}
	}
	
	private function getActiveController($filename){
		$split_filename = explode("/", $filename);
		
		return $split_filename[0];
	}
	
	private function getCurrentPath(){
		$request = parse_url($_SERVER['REQUEST_URI']);
		$path = $request["path"];
		
		$result = trim(str_replace(basename($_SERVER['SCRIPT_NAME']), '', $path), '/');
		
		$result = explode('/', $result);
		$max_level = 6;
		if ($max_level < count($result)) {
			unset($result[0]);
		}
		$result = implode('/', $result);
		
		return substr($result . '/', 0); // was ... , 1) before for some reason
	}
	
	private function getActiveAction($filename){
		$split_filename = explode("/", $filename);
		return $split_filename[1];
	}
	
	/**
	 * Checks if the passed string is the currently active controller.
	 * Useful for handling the navigation's active/non-active link.
	 * @param string $filename
	 * @param string $navigation_controller
	 * @return bool Shows if the controller is used or not
	 */
	private function checkForActiveController($filename, $navigation_controller){
		if ($this->getActiveController($filename) == $navigation_controller) {
			return true;
		}
		// default return
		return false;
	}
	
	/**
	 * Checks if the passed string is the currently active controller-action (=method).
	 * Useful for handling the navigation's active/non-active link.
	 * @param string $filename
	 * @param string $navigation_action
	 * @return bool Shows if the action/method is used or not
	 */
	private function checkForActiveAction($filename, $navigation_action){
		if ($this->getActiveAction($filename) == $navigation_action) {
			return true;
		}
		// default return of not true
		return false;
	}
	
	/**
	 * Checks if the passed string is the currently active controller and controller-action.
	 * Useful for handling the navigation's active/non-active link.
	 * @param string $filename
	 * @param string $navigation_controller_and_action
	 * @return bool
	 */
	private function checkForActiveControllerAndAction($filename, $navigation_controller_and_action){
		$split_filename = explode("/", $filename);
		$active_controller = $split_filename[0];
		$active_action = $split_filename[1];
	
		$split_filename = explode("/", $navigation_controller_and_action);
		$navigation_controller = $split_filename[0];
		$navigation_action = $split_filename[1];
	
		if ($active_controller == $navigation_controller AND $active_action == $navigation_action) {
			return true;
		}
		// default return of not true
		return false;
	}
	
	private function nl2p($string){
		return '<p>' . preg_replace('#(<br />[\r\n]+){2}#', '</p><p>', nl2br($string)) . '</p>';
	}
	
	public function renderPagination(
		$current_page,
		$total_pages,
		$prefix,
		$suffix = '/',
		$prevSuffix = ''
	){
		if ($current_page > 3)
			echo '<a href="' . $prefix . '1' . $suffix . '">1</a>';
		if ($current_page > 4)
			echo '<span class="ellipsis" href="#">&hellip;</span>';
		if ($current_page > 2)
			echo '<a href="' . $prefix . ($current_page - 2) . $suffix . '">' . ($current_page - 2) . '</a>';
		if ($current_page > 1)
			echo '<a href="' . $prefix . ($current_page - 1) . $suffix . ($current_page != 2 ? $prevSuffix : false) . '">' . ($current_page - 1) . '</a>';
		
		echo '<span class="current">' . $current_page . '</span>';
		
		if ($total_pages - $current_page >= 1)
			echo '<a href="' . $prefix . ($current_page + 1) . $suffix . '">' . ($current_page + 1) . '</a>';
		if ($total_pages - $current_page >= 2)
			echo '<a href="' . $prefix . ($current_page + 2) . $suffix . '">' . ($current_page + 2) . '</a>';
		if ($total_pages - $current_page >= 4)
			echo '<span class="ellipsis">&hellip;</span>';
		if ($total_pages - $current_page >= 3)
			echo '<a href="' . $prefix . $total_pages . $suffix . '">' . $total_pages . '</a>';
	}
	
	public function renderPaginationPanel(
		$currentPage,
		$totalPages,
		$prefix,
		$suffix = '/',
		$btnStyles = false,
		$prevSuffix = '',
		$renderPages = true,
		$btnTexts = false
	){
		$btnStyles =
			$btnStyles
				?: [
					false,
					false
				];
		
		$btnTexts =
			$btnTexts
				? [
					$btnTexts[0] ?: 'Prev',
					$btnTexts[1] ?: 'Next'
				]
				: [
					'Prev',
					'Next'
				];
		
		if ($renderPages){
			echo "
			<div class='middle'>
				<div class='pagination'>";
			$this->renderPagination(
				$currentPage,
				$totalPages,
				$prefix,
				$suffix,
				$prevSuffix
			);
			echo "
				</div>
			</div>";
		}
		
		if($currentPage > 1)
			echo "<div class='left'><a class='btn arrow-left" . ($btnStyles[0] ? ' ' . $btnStyles[0] : false) . "' href='" . $prefix . ($currentPage - 1) . $suffix . $prevSuffix . "'>" . $btnTexts[0] . "</a></div>";
		
		if($currentPage < $totalPages)
			echo "<div class='right'><a class='btn arrow-right" . ($btnStyles[1] ? ' ' . $btnStyles[1] : false) . "' href='" . $prefix . ($currentPage + 1) . $suffix . "'>" . $btnTexts[1] . "</a></div>";
	}
}
