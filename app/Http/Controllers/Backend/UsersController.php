<?php
namespace App\Http\Controllers\Backend;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Helpers\ImageHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
class UsersController extends Controller
{
    public function index() 
    {
        $users = User::latest()->paginate(10);

        return view('backend.pages.users.index', compact('users'));
    }

    public function create() 
    {
        return view('backend.pages.users.create');
    }

    public function store(Request $request){
        $this->validate($request, [
            'name' => 'required|min:3|max:50',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required|min:10',
            'password' => 'min:8|required_with:password_confirmation|same:password_confirmation',
            'password_confirmation' => 'min:8'
        ]);
        $input = $request->all();
        $input['password'] = Hash::make($input['password']);
    
        $user = User::create($input);
        if($user){
            return redirect()->route('users')
            ->with('success','User created successfully');
        }
        return redirect()->back()->with('error','Somthings went wrong please try again !.');
    }

    public function edit(User $user) {
        return view('backend.pages.users.edit', [
            'user' => $user,
            'userRole' => $user->roles->pluck('name')->toArray(),
            'roles' => Role::latest()->get()
        ]);
    }

    public function update(Request $request, $id){
        $this->validate($request, [
            'name' => 'required|min:3|max:50',
            'phone_number' => 'required|min:10',
           
        ]);
        $input = $request->all();
        $user = User::find($id);
        $user->update($input);
        DB::table('model_has_roles')->where('model_id',$id)->delete();
        $user->assignRole($request->input('role'));
        return redirect()->route('users')->with('success','User updated successfully');
        
    }

    public function destroy(User $user) {
        $user->delete();
        return redirect()->route('users')->with('success','User deleted successfully');
    }

    public function UserProfile() {
        return view('backend.pages.users.profile');
    }

    public function UserProfileEditForm($id) {
        return view('backend.pages.users.profile-edit');
    }

    public function UserProfileEditFormSubmit(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'nullable|numeric|digits:10',
            'gender' => 'nullable|in:Male,Female,Other',
            'date_of_birth' => 'nullable|date',
            'bio' => 'nullable|string|max:500',
            'profile_img' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'email' => 'nullable|email|unique:users,email,' . $id
        ]);

        try {
            $user = User::findOrFail($id);
            if (Auth::id() !== $user->id) {
                return redirect()->back()->with('error', 'You are not authorized to edit this profile.');
            }
            $emailChanged = false;
            if ($request->has('email') && $request->email != $user->email) {
                $emailChanged = true;
            }
            if ($request->hasFile('profile_img')) {
                if ($user->profile_img) {
                    $old_image_path = public_path('profile-images/' . $user->profile_img);
                    if (file_exists($old_image_path) && !is_dir($old_image_path)) {
                        unlink($old_image_path);
                    }
                }
                $image = $request->file('profile_img');
                $user_name = Str::slug($request->input('name', $user->name));
                $timestamp = Carbon::now()->timestamp;
                $image_file_name = 'profile-' . $user_name . '-' . $timestamp; 
                $baseName = ImageHelper::generateFileName($image_file_name);
                $image_file_name_webp = ImageHelper::uploadSingleImageWebpOnly(
                    $image,
                    $baseName,
                    'user-profile',
                    $user->profile_img
                );
                $user->profile_img = $image_file_name_webp;
            }
            $user->name = $request->input('name', $user->name);
            $user->email = $request->input('email', $user->email);
            $user->phone_number = $request->input('phone_number', $user->phone_number);
            $user->gender = $request->input('gender', $user->gender);
            $user->date_of_birth = $request->input('date_of_birth', $user->date_of_birth);
            $user->bio = $request->input('bio', $user->bio);
            $user->save();
            if ($emailChanged) {
                session(['email_changed' => true]);
            }

            return redirect()->route('profile')
                   ->with('success', 'Profile updated successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                   ->with('error', 'Error updating profile: ' . $e->getMessage())
                   ->withInput();
        }
    }

     public function changePasswordForm()
    {
        return view('backend.pages.users.change-password');
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
            'new_password_confirmation' => 'required'
        ]);
        $user = Auth::user();
        if (!Hash::check($request->current_password, $user->password)) {
            return redirect()->back()->with('error', 'Current password is incorrect.');
        }
        if (Hash::check($request->new_password, $user->password)) {
            return redirect()->back()->with('error', 'New password cannot be same as current password.');
        }
        $user->password = Hash::make($request->new_password);
        $user->password_changed_at = Carbon::now();
        $user->save();
        return redirect()->route('profile')->with('success', 'Password changed successfully. Please login with new password next time.');
    }

}
