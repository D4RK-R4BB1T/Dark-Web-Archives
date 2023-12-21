<?php

namespace App\Http\Controllers\Admin;

use App\Category;
use App\Dispute;
use App\Events\Support\TicketClosed;
use App\Exceptions\RequestException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendMessagesRequest;
use App\Http\Requests\Categories\NewCategoryRequest;
use App\Http\Requests\Purchase\ResolveDisputeRequest;
use App\Offer;
use App\Product;
use App\Purchase;
use App\Ticket;
use App\User;
use App\Vendor;
use App\VendorPurchase;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdminController extends Controller
{
    public function __construct()
    {
        $this -> middleware('admin_panel_access');
    }

    private function categoriesCheck(){
        if(Gate::denies('has-access', 'categories'))
            abort(403);
    }

    private function messagesCheck(){
        if(Gate::denies('has-access', 'messages'))
            abort(403);
    }

    private function disputesCheck()
    {
        if(Gate::denies('has-access', 'disputes'))
            abort(403);
    }

    private function ticketsCheck(){
        if(Gate::denies('has-access', 'tickets'))
            abort(403);
    }

    /**
     * Return home view of category section
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view('admin.index',
            [
                'total_products' => Product::count(),
                'total_purchases' => Purchase::count(),
                'total_daily_purchases' => Purchase::where('updated_at', '>', Carbon::now()->subDay())->where('state', 'delivered')->count(),
                'total_users' => User::count(),
                'total_vendors' => Vendor::count(),
                'avg_product_price' => Offer::averagePrice(),
                'total_spent' => Purchase::totalSpent(),
                'total_earnings_coin' => Purchase::totalEarningPerCoin()
            ]
            );
    }

    /**
     * Return view with the category list
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function categories()
    {
        $this -> categoriesCheck();

        return view('admin.categories',
            [
                'rootCategories' => Category::roots(),
                'categories' => Category::nameOrdered(),
            ]
            );
    }

    /**
     * Accepts the request for the new Category
     *
     * @param NewCategoryRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function newCategory(NewCategoryRequest $request)
    {
        $this -> categoriesCheck();
        try{
            $request -> persist();
            session() -> flash('success', 'You have added new category!');
        }
        catch (RequestException $e){
            session() -> flash('errormessage', $e -> getMessage());
        }
        return redirect() -> back();
    }

    /**
     * Remove category
     *
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function removeCategory($id)
    {
        try {
            $this -> categoriesCheck();
            $catToDelete = Category::findOrFail($id);
            $catToDelete -> delete();

            session() -> flash('success', 'You have successfully deleted category!');
        } catch (\Exception $e){
            session() -> flash('errormessage', $e -> getMessage());
        }

        return redirect() -> back();
    }

    /**
     * Show form for editing category
     *
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function editCategoryShow($id)
    {
        $this -> categoriesCheck();
        $categoryToShow = Category::findOrFail($id);


        return view('admin.category', [
            'category' => $categoryToShow,
            'categories' => Category::nameOrdered(),
        ]);

    }

    /**
     * Accepts request for editing category
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function editCategory($id, NewCategoryRequest $request)
    {
        $this -> categoriesCheck();

        try{
            $request -> persist($id);
            session() -> flash('success', 'You have changed category!');
        }
        catch (RequestException $e){
            session() -> flash('errormessage', $e -> getMessage());
        }
        return redirect() -> route('admin.categories');
    }



    /**
     * Form for the new message
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function massMessage()
    {
        $this -> messagesCheck();

        return view('admin.messages');
    }

    /**
     * Send mass message to group of users
     * 
     * @param SendMessagesRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendMessage(SendMessagesRequest $request)
    {
        $this -> messagesCheck();
        try{
            $noMessages = $request -> persist();
            session() -> flash('success', "$noMessages messages has been sent!");
        }
        catch (RequestException $e){
            $e -> flashError();
        }

        return redirect() -> back();
    }

    /**
     * Return view with the table of disputes
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function disputes()
    {
        $this -> disputesCheck();

        return view('admin.disputes', [
            'allDisputes' => Dispute::paginate(config('marketplace.products_per_page')),
        ]);
    }

    /**
     * Resolve dispute of the purchase
     *
     * @param ResolveDisputeRequest $request
     * @param Purchase $purchase
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resolveDispute(ResolveDisputeRequest $request, Purchase $purchase)
    {
        $this -> disputesCheck();

        try{
            $purchase -> resolveDispute($request -> winner);
            session() -> flash('success', 'You have successfully resolved this dispute!');
        }
        catch (RequestException $e){
            $e -> flashError();
        }

        return redirect() -> back();
    }


    /**
     * Single Purchase view for admin
     *
     * @param Purchase $purchase
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function purchase(Purchase $purchase)
    {
        return view('admin.purchase', [
            'purchase' => $purchase,
        ]);
    }

    /**
     * Displayed all paginated tickets
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function tickets()
    {
        return view('admin.tickets', [
           'tickets' => Ticket::orderByDesc('created_at') -> paginate(config('marketplace.posts_per_page'))
        ]);
    }


    /**
     * Single ticket Admin view
     *
     * @param Ticket $ticket
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function viewTicket(Ticket $ticket)
    {
        return view('admin.ticket', [
            'ticket' => $ticket,
            'replies' => $ticket -> replies() -> orderByDesc('created_at') -> paginate(config('marketplace.posts_per_page')),
        ]);
    }

    /**
     * Solve ticket request
     * 
     * @param Ticket $ticket
     * @return \Illuminate\Http\RedirectResponse
     */
    public function solveTicket(Ticket $ticket)
    {
        $ticket -> solved = !$ticket -> solved;
        $ticket -> save();
        session() -> flash('successmessage', 'The ticket has been solved!');

        event(new TicketClosed($ticket));

        return redirect() -> back();
    }

    /**
     * List of vendor purchases
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function vendorPurchases()
    {
        return view('admin.vendorpurchases', [
            'vendors' => Vendor::orderByDesc('created_at')->paginate(24),
        ]);
    }
    
    
    public function removeTickets(Request $request){
        $type = $request->type;
        if ($type == 'all'){
            foreach (Ticket::all() as $ticket){
                $ticket->delete();
            }
        }
        if ($type == 'solved'){
            foreach (Ticket::where('solved',1)->get() as $ticket){
                $ticket->delete();
            }
        }

        if ($type == 'orlder_than_days'){
            foreach (Ticket::where('created_at', '<' ,Carbon::now()->subDays($request->days))->get() as $ticket){
                $ticket->delete();
            }
        }

        return redirect()->back();


    }
}
