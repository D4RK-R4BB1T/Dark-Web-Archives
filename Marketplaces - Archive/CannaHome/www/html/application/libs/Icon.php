<?php
class Icon {
	private static $icons = [
		'ENVELOPE' => [
			'envelope',
			'&#x2709;'
		],
		'TRUCK' => [
			'truck',
			'&#x1f69a;'
		],
		'SHOPPING_CART' => [
			'cart'
		],
		'GAVEL' => [
			'gavel'
		],
		'CHECK' => [
			'check'
		],
		'BITCOIN' => [
			'bitcoin'
		],
		'STAR_HALF' => [
			'star-half-o'
		],
		'BUBBLES' => [
			'comments'
		],
		'QUESTION_MARK' => [
			'question'
		],
		'EXCLAMATION_MARK_IN_CIRCLE' => [
			'exclamation-circle'
		],
		'EXCLAMATION_MARK_IN_TRIANGLE' => [
			'exclamation-triangle'
		],
		'HOUSE' => [
			'home',
			'&#x1f3e0;'
		],
		'COG' => [
			'cog',
			'&#x2699;'
		],
		'EXCHANGE' => [
			'exchange',
			'&#x1f500;',
			'\1f500'
		],
		'HEART' => [
			'heart',
			'&#x2764;'
		],
		'USER' => [
			'user',
			'&#x1f464;'
		],
		'USERS' => [
			'users',
			'&#x1f465;'
		],
		'TAGS' => [
			'tags',
			'&#x1f3f7;'
		],
		'CODE' => [
			'code'
		],
		'BELL' => [
			'bell'
		],
		'LOCK' => [
			'lock'
		],
		'SHIELD' => [
			'shield'
		],
		'CALENDAR' => [
			'calendar'
		],
		'ELLIPSIS_HORIZONTAL' => [
			'ellipsis-h'
		],
		'THUMBS_UP' => [
			'thumbs-up'
		],
		'THUMBS_DOWN' => [
			'thumbs-down'
		],
		'CARET_UP' => [
			'caret-up'
		],
		'CARET_RIGHT' => [
			'caret-right'
		],
		'CARET_DOWN' => [
			'caret-down'
		],
		'CARET_LEFT' => [
			'caret-left',
			'&#x2b05;'
		],
		'PAPER_PLANE' => [
			'paper-plane'
		],
		'TRASH' => [
			'trash',
			'',
			'\1f5d1'
		],
		'COPY' => [
			'windows'
		],
		'EDIT' => [
			'write'
		],
		'PLUS' => [
			'plus',
			'',
			'\271a'
		],
		'ANGLE_RIGHT' => [
			'angle-right',
			'',
			'\25b6'
		],
		'CAMERA' => [
			'camera',
			'',
			'\1f4f7'
		],
		'LIST' => [
			'th-list'
		],
		'TIMES' => [
			'times',
			'',
			'\2716'
		],
		'FORWARD' => [
			'caret-right'
		],
		'MAP_MARKER' => [
			'map-marker'
		],
		'SEARCH' => [
			'search',
			'&#x1f50d;',
			'\1f50d'
		]
	];
	
	public static function getClass(
		$icon,
		$getIconClass = false
	){
		return	($getIconClass ? ICONS_PREFIX : SPRITES_PREFIX) .
			(
				isset(self::$icons[$icon])
					? self::$icons[$icon][ICONS_INDEX_CLASS]
					: strtolower($icon)
			);
	}
	
	public static function getEntity($icon){
		return self::$icons[$icon][ICONS_INDEX_ENTITY];
	}
}
