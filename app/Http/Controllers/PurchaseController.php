<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Product;
use App\Purchase;
use App\Student;

use Exception;

class PurchaseController extends Controller
{
    public function index(){
        if(request()->getMethod()=='POST'){

        }

        $purchases = Purchase::where('is_deleted', false)
            ->where('type', 'manual')
            ->with('user')
            ->with('student')
            ->with('product')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('purchases.index',[
            'purchases' => $purchases,
            'msg_success' => request()->session()->get('msg_success'),
            'msg_error' => request()->session()->get('msg_error')
        ]);
    }

    public function create(Request $request)
    {
        $purchase = new Purchase();
        $students = Student::where('is_deleted', false)->get();
        $products = Product::where('is_deleted', false)->get();
        if($request->getMethod()=='GET'){
            return view('purchases.create', [
                'purchase'=>$purchase,
                'students'=>$students,
                'products'=>$products
            ]);
        }

        $purchase->students_id = $request->input('students_id');
        $purchase->users_id = Auth::user()->id;
        $purchase->products_id = $request->input('products_id');
        $purchase->description = $request->input('description');
        $purchase->price = $request->input('price');
        $purchase->factor_number = $request->input('factor_number');
        $purchase->type = 'manual';
        $purchase->save();

        $request->session()->flash("msg_success", "پرداخت با موفقیت افزوده شد.");
        return redirect()->route('purchases');
    }

    public function edit(Request $request, $id)
    {
        $purchase = Purchase::where('id', $id)->where('is_deleted', false)->where('type', 'manual')->first();
        if($purchase==null){
            $request->session()->flash("msg_error", "پرداخت پیدا نشد!");
            return redirect()->route('purchases');
        }

        $students = Student::where('is_deleted', false)->get();
        $products = Product::where('is_deleted', false)->get();
        if($request->getMethod()=='GET'){
            return view('purchases.create', [
                'purchase'=>$purchase,
                'students'=>$students,
                'products'=>$products
            ]);
        }

        $purchase->students_id = $request->input('students_id');
        $purchase->users_id = Auth::user()->id;
        $purchase->products_id = $request->input('products_id');
        $purchase->description = $request->input('description');
        $purchase->price = $request->input('price');
        $purchase->factor_number = $request->input('factor_number');
        $purchase->save();

        $request->session()->flash("msg_success", "پرداخت با موفقیت افزوده شد.");
        return redirect()->route('purchases');
    }

    public function delete(Request $request, $id)
    {
        $purchase = Purchase::where('id', $id)->where('is_deleted', false)->first();
        if($purchase==null){
            $request->session()->flash("msg_error", "پرداخت پیدا نشد!");
            return redirect()->route('purchases');
        }

        $purchase->is_deleted = true;
        $purchase->save();

        $request->session()->flash("msg_success", "پرداخت با موفقیت حذف شد.");
        return redirect()->route('purchases');
    }

    //---------------------API------------------------------------
    public function apiAddPurchases(Request $request){
        $purchases = $request->input('purchases', []);
        $ids = [];
        $fails = [];
        foreach($purchases as $purchase){
            if(!isset($purchase['woo_id']) || !isset($purchase['phone']) || !isset($purchase['price'])){
                $fails[] = $purchase;
                continue;
            }
            $purchaseObject = new Purchase;
            $product = Product::where('woo_id', $purchase['woo_id'])->first();
            $student = Student::where('phone', $purchase['phone'])->first();
            if($product == null || $student == null){
                $fails[] = $purchase;
                continue;
            }
            $purchaseObject->products_id = $product->id;
            $purchaseObject->students_id = $student->id;
            $purchaseObject->price = isset($purchase['price'])?$purchase['price']:0;
            $purchaseObject->users_id = 0;
            try{
                $purchaseObject->save();
                $ids[] = $purchaseObject->id;
            }catch(Exception $e){
                $fails[] = $purchase;
            }
        }
        return [
            "added_ids" => $ids,
            "fails" => $fails
        ];
    }

    //---------------------API------------------------------------
    public function apiAddStudents(Request $request){
        $students = $request->input('students', []);
        $ids = [];
        $fails = [];
        foreach($students as $student){
            if(!isset($student['phone']) || !isset($student['last_name'])){
                $fails[] = $student;
                continue;
            }
            $studentObject = new Student;
            foreach($student as $key=>$value){
                $studentObject->$key = $value;
            }
            try{
                $studentObject->save();
                $ids[] = $studentObject->id;
            }catch(Exception $e){
                $fails[] = $student;
            }
        }
        return [
            "added_ids" => $ids,
            "fails" => $fails
        ];
    }
}
