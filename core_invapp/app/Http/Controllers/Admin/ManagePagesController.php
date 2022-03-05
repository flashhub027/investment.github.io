<?php


namespace App\Http\Controllers\Admin;

use App\Models\Page;
use App\Enums\Boolean;
use App\Enums\PageStatus;
use App\Filters\PageFilter;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;

class ManagePagesController extends Controller
{
    public function index(PageFilter $filter)
    {
        $query = Page::filter($filter);
        $sortby = user_meta('page_sortpg', 'date');
        $ordered = user_meta('page_order', 'asc');

        switch ($sortby) {
            case 'date':
                $query->orderBy('created_at', $ordered);
                break;
            case 'visible':
                $query->orderBy('status', $ordered);
                break;
            default:
                $query->orderBy('name', $ordered);
        }

        $iState = (object) get_enums(Boolean::class, false);
        $iStatus = (object) get_enums(PageStatus::class, false);

        $pages = $query->paginate(user_meta('page_perpage', 10))->onEachSide(0);

        return view('admin.manage-content.pages.list', compact('pages', 'iState', 'iStatus'));
    }

    private function getPagesList()
    {
        return Page::select('id', 'name', 'slug')->get();
    }

    public function create()
    {
        $pages = $this->getPagesList();

        $iState = (object) get_enums(Boolean::class, false);
        $iStatus = (object) get_enums(PageStatus::class, false);

        return view('admin.manage-content.pages.form', compact('pages', 'iState', 'iStatus'));
    }

    public function edit($id)
    {
        $pages = $this->getPagesList();
        $pageDetails = Page::where('id', $id)->first();

        $iState = (object) get_enums(Boolean::class, false);
        $iStatus = (object) get_enums(PageStatus::class, false);

        return view('admin.manage-content.pages.form', compact('pages', 'pageDetails', 'iState', 'iStatus'));
    }

    public function validatePageSlug(Request $request)
    {
        $slug = Str::slug($request->get('slug'), '-');
        $request->merge(['slug' => $slug]);

        try {
            $request->validate([
                'slug' => 'bail|required|string|unique:pages,slug,'.$request->get('id')
            ]);
            return response()->json(['error' => false, 'note' => __("Slug is valid to save.")]);
        } catch(ValidationException $e) {
            return response()->json(['error' => true, 'note' => __("Slug should be unique.")]);
        }

    }

    public function save(Request $request)
    {
        $slug = Str::slug(strip_tags($request->get('slug')), '-');
        $request->merge(['slug' => $slug]);

        $input = $request->validate([
            'name' => 'required|string|max:190',
            'slug' => 'required|string|max:190|unique:pages,slug,'.$request->get('id'),
            'menu_name' => 'required|string|max:190',
            'menu_link' => 'nullable|url',
            'title' => 'nullable|string|max:190',
            'subtitle' => 'nullable|string|max:190',
            'content' => 'required',
            'seo' => 'array',
            'params' => 'array',
            'public' => 'required',
        ], [
            'slug.unique' => __("Please enter a unique and valid page slug.")
        ]);

        $input['status'] = ($request->get('status') == PageStatus::ACTIVE) ? PageStatus::ACTIVE : PageStatus::INACTIVE;
        $input['slug'] = Str::slug($input['slug'],'-');
        $input['name'] = strip_tags($input['name']);
        $input['menu_name'] = strip_tags($input['menu_name']);
        $input['title'] = strip_tags($input['title']);
        $input['subtitle'] = strip_tags($input['subtitle']);
        $input['seo'] = array_map('strip_tags_map', $input['seo']);

        if ($id = $request->get('id')) {
            Page::where('id', $id)->update($input);
        } else {
            $page = Page::create($input);
            return response()->json([
                'msg' => __('The page has been successfully :status.', ['status' => __("created")]),
                'redirect' => route('admin.manage.pages.edit', $page->id)
            ]);
        }

        return response()->json(['title' => __("Page Updated"), 'msg' => __('The page has been successfully :status.', ['status' => __("updated")])]);
    }

    public function deletePage(Request $request, $id=null) {
        $pid = ($id) ? $id : (int) $request->get('uid');
        if(empty($pid)) {
            throw ValidationException::withMessages(['invalid' => __('An error occurred. Please try again.')]);
        }

        $page = Page::where('id', $pid)->first();
        $reload = ($request->get('reload') == 'true') ? true : false;
        $redirect = ($request->get('redirect') == 'true') ? route('admin.manage.pages') : false;

        if(!blank($page)) {
            if ($page->trash == Boolean::YES) {
                Page::destroy($page->id);
                return response()->json([
                    'title' => __("Page Deleted"), 'msg' => __('The page has been successfully :status.', ['status' => __("deleted")]), 'timeout' => 1200, 'reload' => $reload, 'redirect' => $redirect
                ]);
            }
            throw ValidationException::withMessages(['delete' => __('Sorry, this page can not be deleted.')]);
        }
        throw ValidationException::withMessages(['delete' => __('Sorry, the page is not found or invalid id.')]);
    }
}
