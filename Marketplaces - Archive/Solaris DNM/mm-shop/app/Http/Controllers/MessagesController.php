<?php
/**
 * File: MessagesController.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Http\Controllers;


use App\Employee;
use App\Http\Requests\MessageAddRequest;
use App\MessengerModels\Message;
use App\MessengerModels\Thread;
use App\Shop;
use Illuminate\Http\Request;

class MessagesController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware('auth');
        \View::share('page', 'messages');
    }

    public function index()
    {
        $threads = Thread::forUser(\Auth::user()->id)
            ->latest('updated_at')->latest('id')
            ->with([
                'participants',
                'latestMessage', 'latestMessage.user'
            ])->paginate(15);
        return view('messages.index', [
            'threads' => $threads
        ]);
    }

    public function showDeleteForm()
    {
        $threads = Thread::forUser(\Auth::user()->id)
            ->latest('updated_at')->latest('id')
            ->with([
                'participants',
                'latestMessage', 'latestMessage.user'
            ])->paginate(15);
        return view('messages.index', [
            'threads' => $threads,
            'deleting' => true
        ]);
    }

    public function delete(Request $request)
    {
        $threads = Thread::forUser(\Auth::user()->id)
            ->whereIn('threads.id', $request->get('threads', []))
            ->paginate(15);

        foreach ($threads as $thread) {
            Message::create([
                'thread_id' => $thread->id,
                'user_id' => \Auth::id(),
                'body' => 'Пользователь ' . \Auth::user()->getPublicName() . ' покинул диалог.',
                'system' => true
            ]);

            $thread->markAsRead(\Auth::user()->id);
            $thread->removeParticipant(\Auth::user()->id);

            if ($thread->participants()->count() == 0) {
                $thread->delete();
            }
        }

        return redirect('/messages', 303)->with('flash_success', 'Диалоги удалены!');
    }

    public function thread(Request $request, $threadId)
    {
        /** @var Thread $thread */
        $thread = Thread::forUser(\Auth::user()->id)
            ->findOrFail($threadId);
        $thread->markAsRead(\Auth::user()->id);

        $messages = $thread->messages()
            ->with(['employee', 'employee.user'])
            ->orderBy('id', 'desc')
            ->paginate(30, ['*'], 'mpage');

        $threads = Thread::forUser(\Auth::user()->id)
            ->latest('updated_at')->latest('id')
            ->with([
                'participants',
                'latestMessage', 'latestMessage.user'
            ])
            ->paginate(15);

        return view('messages.thread', [
            'threads' => $threads,
            'thread' => $thread,
            'messages' => $messages
        ]);
    }

    public function sendMessage(MessageAddRequest $request, $threadId)
    {
        /** @var Thread $thread */
        $thread = Thread::forUser(\Auth::user()->id)->findOrFail($threadId);
        $thread->markAsRead(\Auth::user()->id);
        $thread->activateAllParticipants();

        Message::create([
            'thread_id' => $thread->id,
            'user_id'   => \Auth::id(),
            'body'      => $request->get('message')
        ]);

        return redirect('/messages/' . $threadId . '?page=' . $request->get('page', 1), 303)->with('flash_success', 'Сообщение добавлено!');
    }

    public function threadDelete(Request $request, $threadId)
    {
        /** @var Thread $thread */
        $thread = Thread::forUser(\Auth::user()->id)->findOrFail($threadId);

        Message::create([
            'thread_id' => $thread->id,
            'user_id' => \Auth::id(),
            'body' => 'Пользователь ' . \Auth::user()->getPublicName() . ' покинул диалог.',
            'system' => true
        ]);

        $thread->markAsRead(\Auth::user()->id);
        $thread->removeParticipant(\Auth::user()->id);

        if ($thread->participants()->count() == 0) {
            $thread->delete();
        }

        return redirect('/messages')->with('flash_success', 'Диалог удален.');
    }

    public function employeeInvite(Request $request)
    {
        try {
            $code = $request->get('code', '');
            $invitation = \Crypt::decrypt($code);
        } catch (\Exception $exception) {
            return abort(403);
        }

        if ($invitation['user_id'] !== \Auth::id()) {
            return abort(403);
        }

        $thread = Thread::findOrFail($invitation['thread_id']);

        /** @var Shop $shop */
        $shop = Shop::findOrFail($invitation['shop_id']);

        if (Employee::where('shop_id', $invitation['shop_id'])
            ->where('user_id', $invitation['user_id'])
            ->first()) {
            return redirect('/shop/' . $shop->slug)->with('flash_warning', 'Вы уже являетесь сотрудником магазина!');
        }

        if (($shop->getTotalAvailableEmployeesCount() - $shop->employees()->count()) <= 0) {
            return redirect('/shop/' . $shop->slug)->with('flash_warning', 'Превышен лимит активных сотрудников. Свяжитесь с владельцем магазина.');
        }

        $thread->delete();

        Employee::create([
            'shop_id' => $invitation['shop_id'],
            'user_id' => $invitation['user_id'],
            'description' => 'Работник',
            'role' => Employee::ROLE_SUPPORT
        ]);

        return redirect('/shop/' . $shop->slug)->with('flash_success', 'Вы стали сотрудником магазина!');
    }

}