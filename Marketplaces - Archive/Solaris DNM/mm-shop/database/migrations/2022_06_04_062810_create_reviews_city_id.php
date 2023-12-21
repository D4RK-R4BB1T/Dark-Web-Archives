<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReviewsCityId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('goods_reviews', function (Blueprint $table) {
            $table->integer('city_id')->default(0)->nullable(false)->after('order_id')->index();
        });

        // get city_id from orders
        \App\GoodsReview::all()->each(function ($review) {
            if($order = $review->order) {
                $review->city_id = $order->city_id;
                $review->save();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('goods_reviews', function (Blueprint $table) {
            $table->dropColumn('city_id');
        });
    }
}
