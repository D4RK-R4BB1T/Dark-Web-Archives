<?php
class Notifications
{	
	public $all = array();
	
	function custom(
		$content,
		$anchor = false,
		$dismiss = false,
		$group = 'General',
		$design = array(
			'Color' => 'blue',
			'Icon' => 'default'
		),
		$target = '_self',
		$sound = false
	){
		$design = array_merge(array('Color' => 'blue', 'Icon' => 'default'), $design);
		$this->all[ $group ][] = array(
			'Content'	=> $content,
			'Anchor'	=> $anchor,
			'Dismiss'	=> $dismiss,
			'Design'	=> $design,
			'Target'	=> $target,
			'ID'		=> 'notf-' . crc32($group . $content),
			'Sound'		=> $sound
		);
	}
	
	function quick($identifier, $content = false, $group = false){
		switch($identifier){
			case 'FatalError':
				$this->custom($content ? $content : 'Something went horribly wrong!', false, false, $group ? $group : 'General' , array(
					'Color' => 'yellow',
					'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_TRIANGLE')
				));
			break;
			case 'RequestError':
				$this->custom($content ? $content : 'Something went horribly wrong!', false, '.', $group ? $group : 'General' , array(
					'Color' => 'yellow',
					'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_TRIANGLE')
				));
			break;
			case 'Info':
				$this->custom($content, false, false, $group ? $group : 'General' , array(
					'Color' => 'blue',
					'Icon' => Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE')
				));
			break;
		}
	}
	
	function render( $groups = false, $format=false, $tags = array() ){
		if($groups){
			$notifications_groups = array_merge(
				array_intersect_key(
					array_flip($groups),
					$this->all
				),
				array_intersect_key(
					$this->all,
					array_flip($groups)
				)
			);
		} else {
			$notifications_groups = $this->all;
		}
		
		if(count($notifications_groups) == 0){
			return false;
		}
		
		echo $notifications_groups && isset($tags[0]) ? $tags[0] : false;
		foreach($notifications_groups as $group => $notifications){
			foreach($notifications as $notification){
				switch($format){
					case 'list':
						echo '
							<li id="' . $notification['ID'] . '" class="'.$notification['Design']['Color'].'">
								'.($notification['Dismiss'] ? '<a class="close" href="'.$notification['Dismiss'].'">&times;</a>' : false).'
								<'.
								(
									$notification['Anchor']
										? 	'a href="'.$notification['Anchor'].'"' .
											(
												isset($notification['Target']) && $notification['Target']
													? ' target="' . $notification['Target'] . '"'
													: FALSE
											)
										:  	'div' 
								)
								.'>
									'.($notification['Design']['Icon']=='none' ? '' : '<i class="'.($notification['Design']['Icon']=='default' ? Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE') : $notification['Design']['Icon']).'"></i>').'
									<div>
										<div>
											<span>'.($notification['Content']).'</span>
										</div>
									</div>
								</'.($notification['Anchor'] ? 'a' :  'div' ).'>
							</li>';
					break;
					case 'fixed':
						echo '
							<div id="' . $notification['ID'] . '" class="important-notification '.$notification['Design']['Color'].'">
								'.($notification['Dismiss'] ? '<a class="close" href="'.$notification['Dismiss'].'">&times;</a>' : false).'
								'.
								(
									$notification['Anchor']
										? 	'<a class="notification-link" href="'.$notification['Anchor'].'"' .
											(
												isset($notification['Target']) && $notification['Target']
													? ' target="' . $notification['Target'] . '"'
													: FALSE
											) . '>'
										:  	FALSE
								)
									.($notification['Design']['Icon']=='none' ? '' : '<i class="'.($notification['Design']['Icon']=='default' ? Icon::getClass('EXCLAMATION_MARK_IN_CIRCLE') : $notification['Design']['Icon']).'"></i>').'
									<p>'.($notification['Content']).'</p>
								'.($notification['Anchor'] ? '</a>' :  FALSE ).'
							</div>
						';
					break;
					default:
						echo '
							<div id="' . $notification['ID'] . '" class="notification ' . $notification['Design']['Color'] . '">
								<'.($notification['Anchor'] ? 'a href="'.$notification['Anchor'].'"' : 'div').'>
									'.($notification['Design']['Icon']=='none' ? '' : '<i class="'.$notification['Design']['Icon'].'"></i>').'
									<div>
										<div><span>'.$notification['Content'].'</span></div>
									</div>
								</'.($notification['Anchor'] ? 'a' : 'div').'>
								'.($notification['Dismiss'] ? '<a class="close" href="'.$notification['Dismiss'].'">&times;</a>' : false).'
							</div>';
					break;
				}
			}
		}
		echo $notifications_groups && isset($tags[1]) ? $tags[1] : false;
		return true;
	}
}
