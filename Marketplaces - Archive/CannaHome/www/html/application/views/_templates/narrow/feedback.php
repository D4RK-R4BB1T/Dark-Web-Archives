<?php

// get the feedback (they are arrays, to make multiple positive/negative messages possible)
$feedback_positive = Session::get('feedback_positive');
$feedback_negative = Session::get('feedback_negative');

// echo out positive messages
if (isset($feedback_positive['general'])) {
    foreach ($feedback_positive['general'] as $feedback) {
		echo '<div class="notification green"><div><div><div><span>' . $feedback . '</span></div></div></div><a class="close" href=".">×</a></div>';
    }
}

// echo out negative messages
if (isset($feedback_negative['general'])) {
    foreach ($feedback_negative['general'] as $feedback) {
        echo '<div class="notification red"><div><div><div><span>' . $feedback . '</span></div></div></div><a class="close" href=".">×</a></div>';
    }
}
