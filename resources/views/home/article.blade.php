@extends('layouts.frontend')
@section('title', $article->title)
@section('keywords', $article->keywords)
@section('description', $article->description)
@section('css')
    {!! social_css() !!}
    {!! highlight_css() !!}
@stop
@section('content')
    <div class="col-md-8">
        <div class="box box-solid">
            <!-- /.box-header -->
            <div class="box-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="pull-left">
                            <a href="{{route('home')}}" class="btn bg-black btn-flat btn-sm tag"><i class="fa fa-undo"></i>&nbsp;返回</a>
                        </div>
                        <div class="pull-right">
                            @foreach($article->tags as $tag)
                                <a href="{{route('tag',$tag->id)}}" @switch(($tag->id)%5) @case(0)class="tag btn btn-flat btn-xs bg-black" @break @case(1)class="tag btn btn-flat btn-xs bg-olive" @break @case(2)class="tag btn btn-flat btn-xs bg-blue" @break @case(3)class="tag btn btn-flat btn-xs bg-purple" @break @default class="tag btn btn-flat btn-xs bg-maroon" @endswitch target="_blank"><i class="fa fa-tag"></i>&nbsp;{{$tag->name}}</a>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="text-center article-title">
                            <h2>
                                {{$article->title}}
                            </h2>
                        </div>
                        <div class="content" style="">
                            {!! $article->html !!}
                        </div>
                        <div class="social-share text-center"
                                data-disabled="google,twitter, facebook, diandian,linkedin,douban"></div>
                        <div class="copyright_div">
                            <ul class="copyright">
                                <li><strong>本文作者：</strong>{{$article->author}}</li>
                                <li><strong>本文链接：</strong> {{route('article',$article->id)}}
                                </li>
                                <li><strong>版权声明： </strong>本博客所有文章除特别声明外，均采用 <a
                                        href="https://creativecommons.org/licenses/by-nc/4.0/"
                                        rel="external nofollow" target="_blank">CC BY-NC 4.0</a> 许可协议。转载请注明出处！
                                </li>
                            </ul>
                        </div>
                        <div class="hr-line-dashed"></div>
                        <div class="prev-next">
                            <div class="prev pull-left">
                                @if(blank($prev))
                                @else
                                    <a href="{{route('article',$prev['id'])}}" class="btn bg-black btn-flat btn-block" title="{{ $prev->title }}">
                                        <i class="fa fa-arrow-left"></i>&nbsp;{{re_substr($prev['title'],0,10,true)}}
                                    </a>
                                @endif
                            </div>
                            <div class="next pull-right">
                                @if(blank($next))
                                @else
                                    <a href="{{route('article',$next['id'])}}" class="btn bg-black btn-flat btn-block" title="{{ $next->title }}">
                                        {{re_substr($next['title'],0,10,true)}}&nbsp;<i class="fa fa-arrow-right"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="hr-line-dashed"></div>
                <div class="row">
                    <div class="col-md-12">
                        {{--评论内容--}}
                        <div class="post clearfix">
                            <h4 class="text-bold">评论：</h4>
                            @include('errors.validator')
                            <form role="form" action="{{route('comment_store')}}" method="post">
                                @csrf
                                <input type="hidden" name="article_id" id="article_id" value="{{$article->id}}">
                                <div class="row">
                                    <div class="col-xs-12 form-group">
                                        <textarea class="form-control" style="resize: none;" rows="3" cols="4" name="content" placeholder="请输入评论" required></textarea>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 form-group">
                                        <input type="text" class="form-control" name ="nickname" placeholder="输入评论显示名称 *" required>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <input type="email" class="form-control" name="email" placeholder="输入电子邮件（不会在留言显示）*" required>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <button type="submit" class="btn btn-flat btn-block bg-green">评论</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        @foreach($article->comments as $comment)
                            <div class="post">
                                <div class="user-block">
                                    <img class="img-circle img-bordered-sm" src="{{asset('img/user_avatar.png')}}" alt="{{$comment->nickname}}">
                                    <span class="username">
                                <a href="#">{{ $comment->nickname }}</a>
                                </span>
                                    <span class="description">{{ $comment->created_at }}</span>
                                </div>
                                <!-- /.user-block -->
                                <p>
                                    {{ $comment->content }}
                                </p>
                                @isset($comment->reply)
                                    <div class="post reply-post">
                                        <div class="user-block">
                                            <img class="img-circle img-bordered-sm" src="{{ $config['site_admin_avatar'] }}" alt="{{ $config['site_admin'] }}">
                                            <span class="username">
                                            <a href="#">站长回复</a>
                                        </span>
                                            <span class="description">{{ $comment->updated_at }}</span>
                                        </div>
                                        <!-- /.user-block -->
                                        <p>
                                            {{ $comment->reply }}
                                        </p>
                                    </div>
                                @endisset
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <!-- /.box-body -->
        </div>
    </div>
@stop
@section('js')
    {!! social_js() !!}
    {!! highlight_js() !!}
<script>
    $(function () {
        $("pre code").each(function(i, block) {
            hljs.highlightBlock(block);
        });
        // 新页面跳转
        // $(".content a").attr("target", "_blank");
        $(".content img").addClass('img-responsive');
    });
</script>
@stop
