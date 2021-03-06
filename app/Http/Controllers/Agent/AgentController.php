<?php

namespace App\Http\Controllers\Agent;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Structures;
use App\User;

class AgentController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('auth');
    }


    public function index()
    {
        $agents = User::where('id','>',1)->get();
        foreach($agents as $agent)
        {
            $agent->struct = $agent->structure->wording; 
        }

        return view('admin.agent.agents',compact('agents')); 
    }

    public function form()
    {
        $structuresOptions = "";

        $structures = Structures::all();
        foreach($structures as $structure)
        {
            $structuresOptions .= "<option value=$structure->id>$structure->wording</option>";
        }

        return view('admin.agent.new_agent',compact('structuresOptions'));
    }

    public function create(Request $request)
    { 
        $name = $request->name;
        $email = $request->email;
        $passwd = $request->passwd;
        $cpasswd = $request->cpasswd;
        $structure = $request->structure;

        $rules = [
            'name' => 'unique:users|string|required',
            'email' => 'unique:users|email|required',
            'passwd' => 'required',
            'cpasswd' => 'required',
            'structure' => 'required|integer',
        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails())
        {
            return redirect()->back()->with('validation','error');
        }
        else
        {
            if(strcmp($passwd,$cpasswd)==0)
            {
                $agent = new User;
                $agent->name = $name;
                $agent->email = $email;
                $agent->id_structure = $structure;
                $agent->password = bcrypt($passwd);

                if($agent->save())
                {
                    return redirect()->back()->with('success','success');
                }
                else
                {
                    return redirect()->back()->with('error','error');
                }
            }
            else
            {
                return redirect()->back()->with('validation','error');                
            }
        }
    }

    public function delete($id)
    {
        $agentId = $id;

        $agentIsDeleted = User::find($agentId)->delete();
        if($agentIsDeleted)
        {
            return redirect()->back()->with('success','success');
        }
        else
        {
            return redirect()->back()->with('error','error');
        }
    }

    public function prepareUpdate($id)
    {
        $agent = User::where('id',$id)->first();

        $structuresOptions = "";
        $structureId = $agent->structure->id;
        $structureWording = $agent->structure->wording;
        $structuresOptions .= "<option value=$structureId>$structureWording</option>";
        
        $structures = Structures::where('id','!=',$structureId)->get();
        
        foreach($structures as $structure)
        {
            $structuresOptions .= "<option value=$structure->id>$structure->wording</option>";
        }

        return view('admin.agent.update_agent',compact('structuresOptions','agent'));
    }

    public function update(Request $request)
    {
        $id = $request->id;
        $name = $request->name;
        $email = $request->email;
        $passwd = $request->passwd;
        $cpasswd = $request->cpasswd;
        $structure = $request->structure;

        $rules = [
            'name' => 'string|required',
            'email' => 'email|required',
            'structure' => 'required|integer',
        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails())
        {
            return redirect()->back()->with('validation','error');
        }
        else
        {
            $updateArray = [];
            if(!empty($passwd)&&$passwd!=NULL&& trim($passwd)!="")
            {
                if(strcmp($passwd,$cpasswd)==0)
                {
                    $updateArray["password"] = bcrypt($passwd);
                }
            }
            $updateArray["name"] = $name;
            $updateArray["email"] = $email;
            $updateArray["id_structure"] = $structure;

            $agentIsUpdated = User::find($id)->update($updateArray);
            if($agentIsUpdated)
            {
                return redirect()->back()->with('success','success');
            }
            else
            {
                return redirect()->back()->with('error','error');
            }
        }
    }
}
