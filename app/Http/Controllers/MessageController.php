<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Message;
use App\User;
use App\MessageFlow;

class MessageController extends Controller
{
    public function index(){
        $messageIds = MessageFlow::where('users_id', Auth::user()->id)->pluck('messages_id');
        $messages = Message::where('is_deleted', false)->whereIn('id', $messageIds)->with('flows.user')->with('user')->get();
        // dd($messages);
        return view('messages.index',[
            'messages' => $messages,
            'msg_success' => request()->session()->get('msg_success'),
            'msg_error' => request()->session()->get('msg_error')
        ]);
    }

    public function create(Request $request)
    {
        $users = User::where('is_deleted', false)->orderBy('last_name')->get();
        $message = new Message;
        if($request->getMethod()=='GET'){
            return view('messages.create', [
                "users"=>$users
            ]);
        }

        $attachment = null;

        if($request->file('attachment')){
            $filename = now()->timestamp . '.' . $request->file('attachment')->extension();
            $attachment = $request->file('attachment')->storeAs('messages', $filename, 'public_uploads');
        }

        $message->users_id = Auth::user()->id;
        $message->message = $request->input('message');
        $message->attachment = $attachment;
        $message->save();

        foreach($request->input('recievers_id') as $reciever_id){
            $messageFlow = new MessageFlow;
            $messageFlow->messages_id = $message->id;
            $messageFlow->sender_id = $message->users_id;
            $messageFlow->users_id = $reciever_id;
            $messageFlow->attachment = $attachment;
            $messageFlow->save();
        }

        foreach($request->input('ccs_id') as $reciever_id){
            $messageFlow = new MessageFlow;
            $messageFlow->messages_id = $message->id;
            $messageFlow->sender_id = $message->users_id;
            $messageFlow->users_id = $reciever_id;
            $messageFlow->type = 'cc';
            $messageFlow->attachment = $attachment;
            $messageFlow->save();
        }

        $request->session()->flash("msg_success", "پیام با موفقیت ارسال شد.");
        return redirect()->route('messages');
    }
}
