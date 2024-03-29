<?php

namespace App\Http\Controllers;

use App\Models\Trader;
use App\Models\Routing;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TraderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($request){
            $rows_per_page = $request-> rowsPerPage;
            $page = $request -> page;

            $site_type = $request->site_type;
            $company_name = $request->company_name;
            $routing_id = $request->routing_id;
            $prefecture = $request->prefecture;
            $mobilephone_number = $request->mobilephone_number;
            $telephone_number = $request->telephone_number;
            
            $traders = Trader::where(function ($query) use ($site_type, $company_name, $routing_id, $prefecture, $mobilephone_number, $telephone_number, $page, $rows_per_page) {
                if($site_type)
                   $query->where('site_type', 'LIKE', '%'.$site_type.'%');
                if($company_name)
                   $query->where('company_name', 'LIKE', '%'.$company_name.'%');
                if($routing_id && $routing_id != 0 )
                   $query->where('routing_id', $routing_id);
                if($prefecture && $prefecture != '全て')
                    $query->where('prefecture', $prefecture);
                if($mobilephone_number){
                    $query->orWhere('mobilephone_number', 'LIKE', '%'.$mobilephone_number.'%');
                    $query->orWhere('telephone_number', 'LIKE', '%'.$mobilephone_number.'%');
                }
                if($telephone_number){
                    $query->orWhere('mobilephone_number', 'LIKE', '%'.$telephone_number.'%');
                    $query->orWhere('telephone_number', 'LIKE', '%'.$telephone_number.'%');
                }
                if($page)
                    $query->skip($rows_per_page * $page);
            })->orderBy('id', 'DESC')->paginate($rows_per_page);

            return response()->json([
                'success' => true,
                'data' => $traders
            ]);
        
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->data;
        $trader = Trader::create($data);

        return response()->json([
            'success' => true,
            'data' => $trader,
            'message' => '業者を登録しました。'
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Trader  $trader
     * @return \Illuminate\Http\Response
     */
    public function show(Trader $trader)
    {
        
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Trader  $trader
     * @return \Illuminate\Http\Response
     */
    public function edit(Trader $trader)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Trader  $trader
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Trader $trader)
    {
        $trader->update($request->all());
        return response()->json([
            'success' => true,
            'data' => $trader
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Trader  $trader
     * @return \Illuminate\Http\Response
     */
    public function destroy(Trader $trader)
    {
        $trader->delete();
        return response()->json([ 'success' => true ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function check(Request $request)
    {
        $company_name = $request->company_name;
        $phone_number = str_replace('-', '', $request->telephone_number);

        $traders = Trader::where(function ($query) use ($company_name, $phone_number ) {
                if($company_name)
                    $query->where('company_name', 'LIKE', '%'.$company_name.'%');
                if($phone_number){
                    $query->orWhere('mobilephone_number', 'LIKE', '%'.$phone_number.'%');
                    $query->orWhere('telephone_number', 'LIKE', '%'.$phone_number.'%');
                }
        })->get();

        if(count($traders) > 0)
            return response()->json(['success' => false, 'data' => $traders]);
        else
            return response()->json(['success' => true, 'data' => $traders]);
    }

    /**
     * trader list update from CSV
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addFromCsv(Request $request)
    {
        try{
            if (($open = fopen($request->file, "r"))) {
                while (($data = fgetcsv($open, 5000, ",")) !== FALSE) {
                    // $data = array_map("utf8_encode", $data);
                    $traders[] = $data;
                }
                fclose($open);
            }
        } catch(Exception $ex){
            return response()->json([
                'message' => $ex,
            ], 400);
        }

        array_shift($traders);
        $new_traders = [];
        if($traders){
            foreach ($traders as $trader) {
                $item = array(
                    'id' => isset($trader[0]) ? $trader[0] : '',
                    'date' => isset($trader[1]) ? $trader[1] : '',
                    'site_type' => isset($trader[2]) ? $trader[2] : '',
                    'routing' => isset($trader[3]) ? $trader[3] : '',
                    'membership_type' => isset($trader[4]) ? $trader[4] : '',
                    'prefecture' => isset($trader[5]) ? $trader[5] : '',
                    'cell_content' => isset($trader[6]) ? $trader[6] : '',
                    'company_name' => isset($trader[7]) ? $trader[7] : '',
                    'first_representative' => isset($trader[8]) ? $trader[8] : '',
                    'correspondence_situation' => isset($trader[9]) ? $trader[9] : '',
                    'mobilephone_number' => isset($trader[10]) ? $trader[10] : '',
                    'telephone_number' => isset($trader[11]) ? $trader[11] : '',
                );
                array_push($new_traders, $item);
            }

            foreach ($new_traders as $item) {
                $trader_exist = Trader::where('id', $item['id'])->first();
                if($trader_exist == null) {
                    $routing_id = 0;
                    $prefecture = '';
                    $mobilephone_number = "";
                    $telephonephone_number = "";
                    if($item['routing']) {
                        $routing_item = Routing::where('path_name', $item['routing'])->first();
                        if($routing_item)
                            $routing_id = $routing_item['id'];
                    }
                    if($item['prefecture']) {
                        $prefecture = $item['prefecture'];
                    }
                    if($item['mobilephone_number']){
                        $mobilephone_number = str_replace('-', '', $item['mobilephone_number']);
                    }
                    if($item['telephone_number']){
                        $telephonephone_number = str_replace('-', '', $item['telephone_number']);
                    }
                    Trader::create([
                        // 'tid' =>$item['tid'], 
                        'date' =>$item['date'], 
                        'site_type' =>$item['site_type'], 
                        'routing_id' =>$routing_id, 
                        'membership_type' =>$item['membership_type'], 
                        'prefecture' =>$prefecture,
                        'cell_content' =>$item['cell_content'], 
                        'company_name' =>$item['company_name'], 
                        'first_representative' =>$item['first_representative'], 
                        'correspondence_situation' =>$item['correspondence_situation'], 
                        'mobilephone_number' =>$mobilephone_number,
                        'telephone_number' =>$telephonephone_number 
                    ]); 
                }
                else {
                    $routing_id = 0;
                    $prefecture = "";
                    $mobilephone_number = "";
                    $telephonephone_number = "";

                    if($item['routing']) {
                        $routing_item = Routing::where('path_name', $item['routing'])->first();
                        if($routing_item)
                            $routing_id = $routing_item['id'];
                    }
                    if($item['prefecture']) {
                        $prefecture = $item['prefecture'];
                    }
                    if($item['mobilephone_number']){
                        $mobilephone_number = str_replace('-', '', $item['mobilephone_number']);
                    }
                    if($item['telephone_number']){
                        $telephonephone_number = str_replace('-', '', $item['telephone_number']);
                    }
                    $trader_exist->update([
                        'date' =>$item['date'], 
                        'site_type' =>$item['site_type'], 
                        'routing_id' =>$routing_id, 
                        'membership_type' =>$item['membership_type'], 
                        'prefecture' =>$prefecture, 
                        'cell_content' =>$item['cell_content'], 
                        'company_name' =>$item['company_name'], 
                        'first_representative' =>$item['first_representative'], 
                        'correspondence_situation' =>$item['correspondence_situation'], 
                        'mobilephone_number' =>$mobilephone_number,
                        'telephone_number' =>$telephonephone_number 
                    ]);
                }
            }
            return response()->json([
                'success' => true,
                'message' => '正常に作成されました。'
            ], 200);
        }
        else {
            return response()->json([
                'success' => false,
                'message' => 'csv ファイルが空です。',
            ], 400);
        }
    }
    public function getTrader(Request $request)
    {
        $id = $request->id;
        $trader = Trader::where('id', $id)->first();
        return response()->json([
            'success' => true,
            'data' => $trader
        ]);    
    }
    public function selectedTraderDelete(Request $request)
    {
        foreach ($request->ids as $id) {
            Trader::find($id)->delete();
        }
        return response()->json([
            'success' => true
        ]);    
    }
    public function export_csv(Request $request)
    {
       
        // $allData = Trader::all();
        // return $allData;
        $response = new StreamedResponse(function() {
            // Open output stream
            $handle = fopen('php://output', 'w');
            
            // Add CSV headers
            fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, [
                'ID',
                '日付',
                'サイト種別',
                '経路',
                '会員種別',
                '都道府県',
                '通話内容',
                '社名',
                '初回営業担当',
                '対応状況',
                '携帯番号',
                '固定電話番号'
            ]);
            
            Trader::chunk(100, function($traders) use ($handle) {
                    foreach ($traders as $trader) {
                        $routing_name = '';
                        if($trader->routing_id) {
                            $routing = Routing::where('id', $trader->routing_id)->first();
                            if($routing)
                                $routing_name = $routing['path_name'];
                        }
                        // Add a new row with data
                        fputcsv($handle, [
                            $trader->id,
                            $trader->date,
                            $trader->site_type,
                            $routing_name,
                            $trader->membership_type,
                            $trader->prefecture,
                            $trader->cell_content,
                            $trader->company_name,
                            $trader->first_representative,
                            $trader->correspondence_situation,
                            $trader->mobilephone_number,
                            $trader->telephone_number
                        ]);
                    }
                });
            
            // Close the output stream
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="業者CSVサンプル'.date('d-m-Y').'.csv"',
        ]);

        return $response;
    }
}
