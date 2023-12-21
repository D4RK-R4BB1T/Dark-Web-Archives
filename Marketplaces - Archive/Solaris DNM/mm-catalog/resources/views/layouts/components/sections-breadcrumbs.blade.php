{{-- 
This file is part of MM2-dev project. 
Description: Breadcrumbs and return to search buttons
--}}
<?php
$leftColumnWidth = isset($left_column_width) ? $left_column_width : [6, 6, 6, 5];
?>
<!-- layouts/components/section-breadcrumbs -->
<div class="row">
    <div class="col-xs-{{ $leftColumnWidth[0] }} col-sm-{{ $leftColumnWidth[1] }} col-md-{{ $leftColumnWidth[2] }} col-lg-{{ $leftColumnWidth[3] }} go-back">
        @if(URL::previous())
            <a href="{{ URL::previous() }}">
                <i class="glyphicon glyphicon-circle-arrow-left"></i>
                <span class="hidden visible-xs-inline">{{ __('layout.Back') }}</span>
                <span class="hidden-xs">{{ __('layout.Go back') }}</span>
            </a>
        @endif
    </div>

    <div class="col-xs-{{ 24 - $leftColumnWidth[0] }} col-sm-{{ 24-$leftColumnWidth[1] }} col-md-{{ 24-$leftColumnWidth[2] }} col-lg-{{ 24-$leftColumnWidth[3] }}">
        <ol class="breadcrumb">
            <?php $breadcrumbs = array_filter($breadcrumbs); ?>
            @foreach ($breadcrumbs as $i => $breadcrumb)
                @if ($i !== count($breadcrumbs) - 1 && !is_null($breadcrumb['url']))
                <li><a class="text-orange" href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['title'] }}</a></li>
                @else
                <li class="active">{{ $breadcrumb['title'] }}</li>
                @endif
            @endforeach
        </ol>
    </div> <!-- /.col-sm-9 -->
</div> <!-- /.row -->
<br />
<!-- / layouts/components/section-breadcrumbs -->