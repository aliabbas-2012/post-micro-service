<!DOCTYPE html>
<html lang="en">
    <head>
        <title>View Post Detail</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js"></script>
        <style type="text/css">
            .bold {font-weight: bold!important;}
        </style>
    </head>
    <body>

        <div class="container">
            <h2>Current IP::<?= $ip ?></h2>
            <h3>Post Information</h3> 
            <?php if (!empty($post)) { ?>



                <table class="table table-bordered">
                    <tbody>
                        <tr> 
                            <td class="bold">ID#</td>
                            <td><?= $post->id ?></td>
                            <td class="bold">Post type</td>
                            <td><?= ucfirst($post->postable_type) ?></td>
                            <td class="bold">Local db path</td>
                            <td><?= $post->local_db_path ?></td>
                        </tr>
                        <tr> 
                            <td class="bold">UID#</td>
                            <td><?= $post->uid ?></td>
                            <td class="bold">Status</td>
                            <td><?= $post->status ?></td>
                            <td class="bold">Archive</td>
                            <td><?= $post->archive ? "Deleted" : "Active" ?></td>
                        </tr>

                        <tr> 
                            <td class="bold">Client IP address</td>
                            <td><?= $post->client_ip_address ?></td>
                            <td class="bold">Created at</td>
                            <td><?= $post->created_at ?></td>
                            <td class="bold">Updated at</td>
                            <td><?= $post->updated_at ?></td>
                        </tr>
                        <tr> 
                            <td class="bold">Text content</td>
                            <td colspan="5"><?= $post->text_content ?></td>

                        </tr>
                        <tr> 
                            <td class="bold">Short Code</td>
                            <td><?= $post->short_code ?></td>
                            <td class="bold">Share Url</td>
                            <td colspan="3">
                                <?php
                                if (!empty($post->short_code)) {
                                    $env = (env("APP_ENV") == "production") ? "p" : "s";
                                    $url = "https://web.fayvo.com/f?fsq=" . $post->short_code . "&t=pt&en=$env";
                                    ?>
                                    <a href="<?= $url ?>" target="_blank"><?= $url ?></a>
                                    <?php
                                } else {
                                    echo "Short code not generated";
                                }
                                ?>


                            </td>

                        </tr>
                    </tbody>

                </table>
                <!------ user information display ----->
                <h3>User Information</h3> 
                <table class="table table-bordered">
                    <tbody>
                        <tr> 
                            <th class="bold">ID#</td>
                            <th class="bold">Username</td>
                            <th class="bold">Status</td>
                        </tr>
                        <tr> 
                            <td><?= $post->user->id ?></td>
                            <td><a href="<?= $userUrl . $post->user->username ?>"><?= $post->user->username ?></a></td>
                            <td><?= $post->user->archive ? "Deleted" : "Active" ?></td>
                        </tr>
                    </tbody>
                </table>

                <h3>Post Boxes</h3> 
                <table class="table table-bordered">
                    <tbody>
                        <tr> 
                            <th class="bold">ID#</td>
                            <th class="bold">Name</td>
                            <th class="bold">Status</td>
                        </tr>
                        <?php
                        if (!empty($post->postBoxesPivot)) {
                            foreach ($post->postBoxesPivot as $box) {
                                ?>
                                <tr> 
                                    <td><a href="<?= $boxUrl . $box->id ?>"><?= $box->id ?></a></td>                           
                                    <td><a href="<?= $boxUrl . $box->id ?>"><?= $box->name ?></a></td>                         
                                    <td><?= ($box->status == "A") ? "Public" : ($box->status == "M" ? "Private" : "Friend Only") ?></td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo "<tr><td colspan='3'> No box founed</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
                <h3>Post Tag Users</h3> 
                <table class="table table-bordered">
                    <tbody>
                        <tr> 
                            <th class="bold">ID#</td>
                            <th class="bold">Username</td>
                            <th class="bold">Status</td>
                        </tr>
                        <?php
                        if (!empty($post->postTags)) {
                            foreach ($post->postTags as $tag) {
                                ?>
                                <tr> 
                                    <td><a href="<?= $userUrl . $tag->user->id ?>"><?= $tag->user->id ?></a></td>
                                    <td><a href="<?= $userUrl . $tag->user->username ?>"><?= $tag->user->username ?></a></td>
                                    <td><?= ($tag->user->archive == 1) ? "Deleted" : "Active" ?></td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo "<tr><td colspan='3'> No media founed</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>


                <h3>Post Media</h3> 
                <table class="table table-bordered">
                    <tbody>
                        <?php
                        if (!empty($post->postable->postMedia)) {
                            foreach ($post->postable->postMedia as $media) {
                                ?>
                                <tr><td colspan="6"></td></tr> 
                                <tr> 
                                    <td class="bold">ID#</td>
                                    <td><?= $media->id ?></td>
                                    <td class="bold">Type</td>
                                    <td><?= ucfirst($media->file_type) ?></td>
                                    <td class="bold">Bg Color</td>
                                    <td style="background-color:<?= $media->bg_color ?>"><?= $media->bg_color ?></td>
                                </tr>
                                <tr> 
                                    <td class="bold">Media Url</td>
                                    <td colspan="5"><a href="<?= $media->source ?>" target="_blank"><?= $media->source ?></a></td>
                                </tr>

                                <?php
                            }
                        } else {
                            echo "<tr><td colspan='6'> No media founed</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>





                <?php if (!empty($post->postable_type == 'search')) { ?>

                    <h3>Search Post</h3> 
                    <table class="table table-bordered">
                        <tbody>
                            <?php
                            $attrs = $post->postable->toArray();
                            foreach (array_keys($attrs) as $value) {
                                if (in_array($value, ['post_media', 'postable']))
                                    continue;
                                ?>
                                <tr> 
                                    <td class="bold"><?= $value ?></td>
                                    <td>
                                        <?php
                                        if (filter_var($attrs[$value], FILTER_VALIDATE_URL) === FALSE) {
                                            echo $attrs[$value];
                                        } else {
                                            echo '<a href="' . $attrs[$value] . '" target="_blank">' . $attrs[$value] . '</a>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>

                <?php } ?>




                <?php if (!empty($post->postable->postable)) { ?>

                    <h3>Post Search Attributes</h3> 
                    <table class="table table-bordered">
                        <tbody>
                            <?php
                            $attrs = $post->postable->postable->toArray();
                            foreach (array_keys($attrs) as $value) {
                                ?>
                                <tr> 
                                    <td class="bold"><?= $value ?></td>
                                    <td>
                                        <?php
                                        if (filter_var($attrs[$value], FILTER_VALIDATE_URL) === FALSE) {
                                            echo $attrs[$value];
                                        } else {
                                            echo '<a href="' . $attrs[$value] . '" target="_blank">' . $attrs[$value] . '</a>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>

                <?php } ?>






            <?php } else { ?>
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <th>No post found</th>
                        </tr>
                    </tbody>

                </table>
            <?php } ?>
        </div>

    </body>
</html>
