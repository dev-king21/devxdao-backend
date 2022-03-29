<?php

namespace App\Http\Controllers\Discourse;

use App\Http\Controllers\Controller;
use App\Proposal;
use App\Services\DiscourseService;
use App\Services\TopicPostReactionService;
use App\TopicPostReaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    public function show(DiscourseService $discourse, $post)
    {
        return $discourse->post($post, $discourse->getUsername(Auth::user()));
    }

    public function destroy(DiscourseService $discourse, $post)
    {
        $response = $discourse->deletePost($post, $discourse->getUsername(Auth::user()));

        $proposal = Proposal::where('discourse_topic_id', $response['topic_id'])->first();

        if ($proposal) {
            $proposal->topic_posts_count = $response['post_number'];
            $proposal->save();
        }

        return $response;
    }

    public function update(Request $request, DiscourseService $discourse, $post)
    {
        $response = $discourse->updatePost(
            $post,
            ['post' => ['raw' => $request->raw]],
            Auth::user()->profile->forum_name
        );

        if (isset($response['failed'])) {
            return $response;
        }

        $posts = $discourse->mergePostsWithDxD([$response['post']]);

        return ['post' => head($posts)];
    }

    public function toggleLike(DiscourseService $discourse, $post)
    {
        $username = $discourse->getUsername(Auth::user());

        return $discourse->isLikedTo($post, $username)
            ? $discourse->unlike($post, $username)
            : $discourse->like($post, $username);
    }

    public function upVote(TopicPostReactionService $reaction, $post)
    {
        $reaction->react(Auth::user(), $post, TopicPostReaction::UP_VOTE);

        return [
            'success' => true,
            'data' => $reaction->format($post),
            'message' => 'Upvoted'
        ];
    }

    public function downVote(TopicPostReactionService $reaction, $post)
    {
        $reaction->react(Auth::user(), $post, TopicPostReaction::DOWN_VOTE);

        return [
            'success' => true,
            'data' => $reaction->format($post),
            'message' => 'Downvoted'
        ];
    }
}
