<?php
/**
 * File: MessagesController.php
 * This file is part of MM2-dev project.
 * Do not modify if you do not know what to do.
 */

namespace App\Http\Controllers\Shops\Management;


use App\Employee;
use App\Http\Requests\MessageAddRequest;
use App\Http\Requests\ThreadInviteRequest;
use App\MessengerModels\Message;
use App\MessengerModels\Participant;
use App\MessengerModels\Thread;
use App\Shop;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

class MessagesController extends ManagementController
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware(function($request, $next) {
            $this->authorize('management-sections-messages');
            return $next($request);
        });

        \View::share('page', 'messages');
    }

    public function index(Request $request)
    {
        $threads = Thread::forUser(-$this->shop->id)
            ->latest('updated_at')->latest('id')
            ->with([
                'participants',
                'latestMessage', 'latestMessage.user'
            ])->paginate(15);

        return view('shop.management.messages.index', [
            'threads' => $threads
        ]);
    }

    public function showDeleteForm()
    {
        $threads = Thread::forUser(-$this->shop->id)
            ->latest('updated_at')->latest('id')
            ->with([
                'participants',
                'latestMessage', 'latestMessage.user'
            ])->paginate(15);
        return view('shop.management.messages.index', [
            'threads' => $threads,
            'deleting' => true
        ]);
    }

    public function delete(Request $request)
    {
        $threads = Thread::forUser(-$this->shop->id)->whereIn('threads.id', $request->get('threads', []))->paginate(15);
        foreach ($threads as $thread) {
            Message::create([
                'thread_id' => $thread->id,
                'user_id' => -$this->shop->id,
                'body' => $this->shop->title . ' покинул диалог.',
                'system' => true
            ]);

            $thread->markAsRead(-$this->shop->id);
            $thread->removeParticipant(-$this->shop->id);

            if ($thread->participants()->count() == 0) {
                $thread->delete();
            }
        }

        return redirect('/shop/management/messages', 303)->with('flash_success', 'Диалоги удалены!');
    }


    public function thread(Request $request, $threadId)
    {
        /** @var Thread $thread */
        $thread = Thread::forUser(-$this->shop->id)
            ->findOrFail($threadId);
        $thread->markAsRead(-$this->shop->id);

        $messages = $thread->messages()
            ->with(['employee', 'employee.user'])
            ->orderBy('id', 'desc')
            ->paginate(30, ['*'], 'mpage');

        $threads = Thread::forUser(-$this->shop->id)
            ->latest('updated_at')->latest('id')
            ->with([
                'participants',
                'latestMessage', 'latestMessage.user'
            ])->paginate(15);

        $orderEmployee = null;
        $orderEmployeeInvited = true;
        if ($thread->order && $position = $thread->order->position) {
            $orderEmployee = $position->employee;
            if ($orderEmployee && \Auth::user()->employee->id !== $orderEmployee->id && !$thread->hasParticipant($orderEmployee->user_id)) {
                $orderEmployeeInvited = false;
            }
        }

        $participants = $thread->participantsUserIds(\Auth::user()->id);
        $allEmployees = Employee::with(['user'])->get();
        $employees = [
            'participants' => $allEmployees->filter(function ($employee) use ($participants) {
                return in_array($employee->user->id, $participants) && $employee->id > 1;
            }),
            'rest' => $allEmployees->filter(function ($employee) use ($participants) {
                return !in_array($employee->user->id, $participants) && $employee->id > 1;
            })
        ];
        unset($allEmployees);

        return view('shop.management.messages.thread', [
            'threads' => $threads,
            'thread' => $thread,
            'messages' => $messages,
            'orderEmployee' => $orderEmployee,
            'orderEmployeeInvited' => $orderEmployeeInvited,
            'employees' => $employees
        ]);
    }

    public function threadAddEmployee(Request $request, $threadId)
    {
        if (\Session::get('_token') !== $request->get('_token')) {
            throw new TokenMismatchException;
        }

        /** @var Thread $thread */
        $thread = Thread::forUser(-$this->shop->id)
            ->findOrFail($threadId);

        if (!$thread->order || !($position = $thread->order->position)) {
            return redirect('/shop/management/messages/' . $threadId)->with('flash_warning', 'Не удалось найти заказ.');
        }

        $orderEmployee = $position->employee;
        if (!$orderEmployee && \Auth::user()->employee->id !== $orderEmployee->id) {
            return redirect('/shop/management/messages/' . $threadId)->with('flash_warning', 'Не удалось найти сотрудника.');
        }

        if ($thread->hasParticipant($orderEmployee->user_id)) {
            return redirect('/shop/management/messages/' . $threadId)->with('flash_warning', 'Сотрудник уже добавлен в диалог.');
        }

        $thread->addParticipant($orderEmployee->user_id);

        Message::create([
            'thread_id' => $thread->id,
            'user_id' => -$this->shop->id,
            'employee_id' => \Auth::user()->employee->id,
            'body' => 'Сотрудник ' . $orderEmployee->user->getPublicName() . ' добавлен в диалог.',
            'system' => true
        ]);

        return redirect('/shop/management/messages/' . $threadId)->with('flash_success', 'Сотрудник добавлен в диалог.');
    }

    public function sendMessage(MessageAddRequest $request, $threadId)
    {
        /** @var Thread $thread */
        $thread = Thread::forUser(-$this->shop->id)->findOrFail($threadId);
        $thread->markAsRead(-$this->shop->id);

        Message::create([
            'thread_id'   => $thread->id,
            'user_id'     => -$this->shop->id,
            'employee_id' => \Auth::user()->employee->id,
            'body'        => $request->get('message')
        ]);

        return redirect('/shop/management/messages/' . $threadId . '?page=' . $request->get('page', 1), 303)->with('flash_success', 'Сообщение добавлено!');
    }

    public function threadDelete(Request $request, $threadId) {
        /** @var Thread $thread */
        $thread = Thread::forUser(-$this->shop->id)->findOrFail($threadId);

        Message::create([
            'thread_id' => $thread->id,
            'user_id' => -$this->shop->id,
            'body' => $this->shop->title . ' покинул диалог.',
            'system' => true
        ]);

        $thread->markAsRead(-$this->shop->id);
        $thread->removeParticipant(-$this->shop->id);

        if ($thread->participants()->count() == 0) {
            $thread->delete();
        }

        return redirect('/shop/management/messages')->with('flash_success', 'Диалог удален!');
    }

    public function showNewThreadForm(Request $request)
    {
        /** @var User $receiver */
        $receiver = User::find($request->get('user'));

        $threads = Thread::forUser(-$this->shop->id)
            ->latest('updated_at')->latest('id')
            ->with([
                'participants',
                'latestMessage', 'latestMessage.user'
            ])->paginate(15);

        return view('shop.management.messages.new', [
            'receiver' => $receiver,
            'threads' => $threads
        ]);
    }

    public function newThread(Request $request)
    {
        /** @var User $receiver */
        $this->validate($request, [
            'receiver' => 'required|exists:users,username',
            'title' => 'required|min:5',
            'message' => 'required|max:6000',
            'sender' => 'required|in:shop,user'
        ]);

        $receiver = User::whereUsername($request->get('receiver'))->firstOrFail();

        if ($receiver->id === \Auth::user()->id && $request->get('sender') === 'user') {
            return redirect('/shop/management/messages/new')->withInput()->with('flash_warning', 'Вы не можете отправить сообщение самому себе.');
        }

        $thread = Thread::create([
            'subject' => $request->get('title'),
        ]);

        Message::create([
            'thread_id' => $thread->id,
            'user_id' => ($request->get('sender') === 'shop')
                ? -$this->shop->id
                : \Auth::user()->id,
            'body' => $request->get('message'),
            'system' => false
        ]);

        Participant::create([
            'thread_id' => $thread->id,
            'user_id' => ($request->get('sender') === 'shop')
                ? -$this->shop->id
                : \Auth::user()->id,
            'last_read' => new Carbon()
        ]);

        $thread->addParticipant($receiver->id);
        return redirect((($request->get('sender') === 'shop')
                ? '/shop/management/messages/'
                : '/messages/') . $thread->id, 303)->with('flash_success', 'Сообщение создано!');
    }

    public function threadInvite(ThreadInviteRequest $request, $threadId)
    {
        $thread = Thread::forUser(-$this->shop->id)->findOrFail($threadId);
        $user = Employee::find($request->get('employee_id'))->user;

        if ($thread->hasParticipant($user->id)) {
            return redirect('/shop/management/messages/' . $threadId)->with('flash_warning', 'Сотрудник уже добавлен в диалог.');
        }

        $thread->addParticipant($user->id);
        Message::create([
            'thread_id' => $thread->id,
            'user_id' => -$this->shop->id,
            'employee_id' => \Auth::user()->employee->id,
            'body' => 'Сотрудник ' . $user->getPublicName() . ' добавлен в диалог.',
            'system' => true
        ]);

        return redirect('/shop/management/messages/' . $threadId)->with('flash_success', 'Сотрудник добавлен в диалог.');
    }

    public function threadKick(ThreadInviteRequest $request, $threadId)
    {
        $thread = Thread::forUser(-$this->shop->id)->findOrFail($threadId);
        $user = Employee::find($request->get('employee_id'))->user;

        if (!$thread->hasParticipant($user->id)) {
            return redirect('/shop/management/messages/' . $threadId)->with('flash_warning', 'Этого сотрудника нет в диалоге.');
        }

        if($user->id == \Auth::user()->id) {
            return redirect('/shop/management/messages/' . $threadId)->with('flash_warning', 'Вы не можете выгнать себя.');
        }

        $thread->removeParticipant($user->id);
        Message::create([
            'thread_id' => $thread->id,
            'user_id' => -$this->shop->id,
            'employee_id' => \Auth::user()->employee->id,
            'body' => 'Сотрудник ' . $user->getPublicName() . ' удален из диалога.',
            'system' => true
        ]);

        return redirect('/shop/management/messages/' . $threadId)->with('flash_success', 'Сотрудник добавлен в диалог.');
    }
}