<?php

namespace App\Http\Controllers;

use App\Question;
use App\User;
use App\Like;
use App\Dislike;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Reply;

class QuestionController extends Controller
{
    /**
     * Display a listing of the resource on the home page.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //HOME PAGE
        //get question data
        $question_data = DB::table('questions')
            ->join('users', 'users.id', '=', 'questions.user_id')
            ->select('questions.id', 'questions.title', 'questions.content', 'questions.nb_replies',
                'questions.labels', 'questions.user_id', 'questions.created_at', 'questions.updated_at', 'users.name')
            ->orderBy('created_at', 'asc')
            ->get();

        //return view with necessary information
        return view('welcome', ['question_data' => $question_data, 'label_data' => $question_data,
            'background_color_label' => '', 'label_clicked' => '']);
    }

    /**
     * Display the question details on the question page.
     *
     * @param  $id identifier of the question to show
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //QUESTION PAGE
        //get necessary data from db to display
        $question = Question::find($id);
        $user = User::where('id', $question->user_id)->get();
        $replies = Reply::where('question_id', $id)->get();
        $qOwner = ($question->user_id == Auth::id());
        $likes = Like::where('question_id', $id)->get();
        $dislikes = Dislike::where('question_id', $id)->get();

        //get labels for sidebar display
        $label_data = DB::table('questions')
            ->select('questions.labels')->get();

        //get nameInfo. from the db for reply display
        $name_data = DB::table('users')
            ->select('users.id', 'users.name')
            ->get();

        return view('question',
            ['question' => $question, 'user' => $user, 'replies' => $replies, 'qOwner' => $qOwner, 'likes' => $likes, 'dislikes' => $dislikes,
                'label_data' => $label_data, 'name_data' => $name_data]);
    }

    /**
     * Displays the form to create a new question.
     *
     * @return \Illuminate\Http\Response
     */
    public function showNewQuestion()
    {
        //get labels for sidebar display
        $label_data = DB::table('questions')
            ->select('questions.labels')->get();

        return view('ask', ['label_data' => $label_data]);
    }

    /**
     * Stores a newly created question in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'title' => 'required|unique:questions|max:255',
            'content' => 'required',
        ]);


        if (Auth::check()) {
            $question = new Question;
            $question->user_id = Auth::id();
            $question->title = $request->get('title');
            $question->content = $request->get('content');
            $question->nb_replies = 0;

            //make sure labels have a value
            if ($request->get('labels') == null)
                $question->labels = '';
            else
                $question->labels = $request->get('labels');

            $question->save();

            return redirect('/home');
        } else
            return redirect('/ask');

    }

    /**
     * Displays home page with the questions ordered a specific user selected way.
     *
     * @params order the condition to filter on
     * @params direction ascending or descending
     * @params page which page it will start to be displayed on (pagination)
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function order($order, $direction, $page)
    {
        ($direction == "asc") ? $dir = 'asc' : $dir = 'desc';

        switch ($order) {
            case 'Date Created':
                $question_data = DB::table('questions')
                    ->join('users', 'users.id', '=', 'questions.user_id')
                    ->select('questions.id', 'questions.title', 'questions.content', 'questions.nb_replies',
                        'questions.labels', 'questions.user_id', 'questions.created_at', 'questions.updated_at', 'users.name')
                    ->orderBy('created_at', "$dir")
                    ->get();
                break;
            case 'Number of Replies':
                $question_data = DB::table('questions')
                    ->join('users', 'users.id', '=', 'questions.user_id')
                    ->select('questions.id', 'questions.title', 'questions.content', 'questions.nb_replies',
                        'questions.labels', 'questions.user_id', 'questions.created_at', 'questions.updated_at', 'users.name')
                    ->orderBy('nb_replies', "$dir")
                    ->get();
                break;
            case 'Title':
                $question_data = DB::table('questions')
                    ->join('users', 'users.id', '=', 'questions.user_id')
                    ->select('questions.id', 'questions.title', 'questions.content', 'questions.nb_replies',
                            'questions.labels', 'questions.user_id', 'questions.created_at', 'questions.updated_at', 'users.name')
                    ->orderBy('title', "$dir")
                    ->get();
                break;
            case 'Last Updated':
                $question_data = DB::table('questions')
                    ->join('users', 'users.id', '=', 'questions.user_id')
                    ->select('questions.id', 'questions.title', 'questions.content', 'questions.nb_replies',
                        'questions.labels', 'questions.user_id', 'questions.created_at', 'questions.updated_at', 'users.name')
                    ->orderBy('updated_at', "$dir")
                    ->get();
                break;
            default:
                $question_data = DB::table('questions')
                    ->join('users', 'users.id', '=', 'questions.user_id')
                    ->select('questions.id', 'questions.title', 'questions.content', 'questions.nb_replies',
                        'questions.labels', 'questions.user_id', 'questions.created_at', 'questions.updated_at', 'users.name')
                    ->get();
        }

        return view('common_questions', ['question_data' => $question_data, 'page' => $page]);
    }


    /**
     * Displays the questions that have been selected by the specific label.
     *
     * @param $label the label that was clicked on
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws
     */
    public function filterLabel($label)
    {
        //questions will not be filtered
        if ($_POST['color'] == 0) {
            $question_data = DB::table('questions')->join('users', 'users.id', '=', 'questions.user_id')
                ->select('questions.id', 'questions.title', 'questions.content', 'questions.nb_replies',
                    'questions.labels', 'questions.user_id', 'questions.created_at', 'questions.updated_at', 'users.name')->get();
        } else {
            //if a new label was clicked, return the question of that label
            $question_data = DB::table('questions')->join('users', 'users.id', '=', 'questions.user_id')
                ->select('questions.id', 'questions.title', 'questions.content', 'questions.nb_replies',
                    'questions.labels', 'questions.user_id', 'questions.created_at', 'questions.updated_at', 'users.name')
                ->where('questions.labels', 'like', "%$label%")->get();
        }

        $label_data = DB::table('questions')->join('users', 'users.id', '=', 'questions.user_id')
            ->select('questions.id', 'questions.title', 'questions.content', 'questions.nb_replies',
                'questions.labels', 'questions.user_id', 'questions.created_at', 'questions.updated_at', 'users.name')
            ->get();

        //return view to ajax
        $returnHTML = view('welcome', ['question_data' => $question_data, 'label_data' => $label_data,
            'background_color_label' => $_POST['color'], 'label_clicked' => $label])->render();

        return response()->json(array('success' => true, 'html' => $returnHTML));
    }
}
