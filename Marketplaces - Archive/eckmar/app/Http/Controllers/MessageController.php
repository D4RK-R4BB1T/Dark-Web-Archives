<?php

namespace App\Http\Controllers;

use App\Conversation;
use App\Exceptions\RequestException;
use App\Http\Requests\Profile\DecryptMessagesRequest;
use App\Http\Requests\Profile\NewConversationRequest;
use App\Http\Requests\Profile\NewMessageRequest;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;


/**
 * Extends profile controller beacuse needs all middleware
 *
 * Class MessageController
 * @package App\Http\Controllers
 */
class MessageController extends ProfileController
{
    /**
     * MessageController constructor.
     */
    public function __construct()
    {
        // Must be logged in
        $this -> middleware('auth');
    }

    /**
     * Returns the view with the all conversations and view of the one conversation if it is set
     *
     * @param Conversation|null $conversation
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function messages(Conversation $conversation = null,Request $request)
    {
        if (!is_null($conversation)) {
            // only people in chat can view conversation
            $this->authorize('view', $conversation);

            // Mark messages as read
            $conversation->markMessagesAsRead();
        }
        $other_party_from_url = $request->otherParty;
        $other_party_from_session = session()->get('new_conversation_other_party');
        if (!$other_party_from_session){
            $new_conversation_other_party = $other_party_from_url;
        } else {
            session()->forget('new_conversation_other_party');
            $new_conversation_other_party = $other_party_from_session;
        }

        return view('profile.messages', [
            'new_conversation_other_party' => $new_conversation_other_party,
            'conversation' => $conversation,
            'usersConversations' => auth() -> user() -> conversations() -> orderByDesc('updated_at') -> take(10) -> get(), // list of users conversations
            'conversationMessages' => $conversation != null ? 
                                $conversation -> messages() -> orderByDesc('created_at') 
                                -> paginate(config('marketplace.products_per_page')) : null, // messages of the conversation
        ]);

    }

    /**
     *  List of all paginated Conversations
     *
     *  @return view
     */
    public function listConversations()
    {
        return view('profile.conversations', [
            'usersConversations' => auth() -> user() -> conversations() -> orderByDesc('updated_at') -> paginate(config('marketplace.products_per_page')),
        ]);
    }

    /**
     * Find old conversation or make new and redirect the the page
     *
     * @param NewConversationRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function startConversation(NewConversationRequest $request)
    {
        $otherUser = User::where('username', $request -> username) -> first();

        $newOldConversation = Conversation::findWithUsersOrCreate(auth() -> user(), $otherUser);


        // Redirect to new message via GET request
        return redirect() -> route('profile.messages.send.message', [
            'conversation' => $newOldConversation,
            'message' => $request -> message
        ]) ;
    }

    /**
     * Request for the new message, POST
     * Response is redirect back
     *
     * @param NewMessageRequest $request
     * @param Conversation $conversation
     */
    public function newMessage(NewMessageRequest $request, Conversation $conversation)
    {
        try{
            $this -> authorize('update', $conversation);
            $conversation -> updateTime(); // update time of the conversation
            $request -> persist($conversation); // Persist the request
            session() -> flash('success', 'New message has been posted');
        }
        catch (RequestException $e){
            $e -> flashError();
        }

        // Redirect to conversation
        return redirect() -> route('profile.messages', $conversation);
    }

    /**
     * Shows page that requests password to decrypt rsa key
     */
    public function decryptKeyShow(Request $request) {

        return view('profile.messagekey');
    }
    /**
     * Shows page that requests password to decrypt rsa key
     */
    public function decryptKeyPost(DecryptMessagesRequest $request) {
        try{
            $request->persist();
        } catch(RequestException $e){
            $e -> flashError();
            return redirect()->back();
        }
        return redirect()->route('profile.messages');

    }
}
