<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cmgmyr\Messenger\Models\Message;
use Cmgmyr\Messenger\Models\Participant;
use App\User;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Cmgmyr\Messenger\Models\Thread;
use Zend\Diactoros\Response;

class MessagesController extends Controller
{
    public $successCode = 200;
    /**
     * List all threads by a user
     * @param $user_id
     * @return Response
     */
    public function index($user_id = false){
        $threads = null;
        $threads_count = 10;
        if ($user_id){
            $threads = Thread::with('users')->forUser($user_id)->latest('updated_at')->paginate($threads_count);
        } else {
            $threads = Thread::with('users')->forUser(Auth::user()->id())->latest('updated_at')->paginate($threads_count);
        }

        foreach ($threads as $thread){
            $latest_message = Thread::find($thread->id)->getLatestMessageAttribute();
            $user = User::find($latest_message->user_id);

            $thread->latest_message = $latest_message;
            $thread->user = $user;
        }

        return response()->json($threads, $this->successCode);
    }

    /**
     * Create thread and add participants
     * @param $request
     * @return Response
     */
    public function create_thread(Request $request){
        //Log::error(print_r($request->all(), true));

        $thread = Thread::create([
            'subject' => $request['subject'],
        ]);

        // Create message
        Message::create([
            'thread_id' => $thread->id,
            'user_id' => $request['sender_id'],
            'body' => $request['message'],
        ]);

        // Add thread initiator
        Participant::create([
            'thread_id' => $thread->id,
            'user_id' => $request['sender_id'],
            'last_read' => new Carbon,
        ]);

        // Add Recipients
        foreach ($request->participants as $participant){
            $thread->addParticipant($participant);
        }

        $threads = Thread::with('users')->forUser($request['sender_id'])->latest('updated_at')->get();
        return response()->json($threads, $this->successCode);
    }

    /**
     * @param $thread_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function get_latest_message_from_thread($thread_id){
        $latest_message = Thread::find($thread_id)->getLatestMessageAttribute();
        $user = User::find($latest_message->user_id);
        $latest_message->user = $user;
        return response()->json([$latest_message, $user], $this->successCode);
    }
}
