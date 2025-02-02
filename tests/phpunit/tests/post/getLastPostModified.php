<?php

/**
 * @group post
 */
class Tests_Post_GetLastPostModified extends WP_UnitTestCase
{

    /**
     * @ticket 47777
     */
    public function test_get_lastpostmodified()
    {
        global $wpdb;

        $post_post_date_first = '2020-01-30 16:09:28';
        $post_post_modified_first = '2020-02-28 17:10:29';
        $post_post_date_last = '2020-03-30 18:11:30';
        $post_post_modified_last = '2020-04-30 19:12:31';

        $book_post_date_first = '2019-05-30 20:09:28';
        $book_post_modified_first = '2019-06-30 21:10:29';
        $book_post_date_last = '2019-07-30 22:11:30';
        $book_post_modified_last = '2019-08-30 23:12:31';

        // Register book post type.
        register_post_type('book', ['has_archive' => true]);

        // Create a simple post.
        $simple_post_id_first = self::factory()->post->create(
            [
                'post_title' => 'Simple Post First',
                'post_type' => 'post',
                'post_date' => $post_post_date_first,
            ],
        );

        $simple_post_id_last = self::factory()->post->create(
            [
                'post_title' => 'Simple Post Last',
                'post_type' => 'post',
                'post_date' => $post_post_date_last,
            ],
        );

        // Create custom type post.
        $book_cpt_id_first = self::factory()->post->create(
            [
                'post_title' => 'Book CPT First',
                'post_type' => 'book',
                'post_date' => $book_post_date_first,
            ],
        );

        $book_cpt_id_last = self::factory()->post->create(
            [
                'post_title' => 'Book CPT Last',
                'post_type' => 'book',
                'post_date' => $book_post_date_last,
            ],
        );

        // Update `post_modified` and `post_modified_gmt`.
        $wpdb->update(
            $wpdb->posts,
            [
                'post_modified' => $post_post_modified_first,
                'post_modified_gmt' => $post_post_modified_first,
            ],
            [
                'ID' => $simple_post_id_first,
            ],
        );

        $wpdb->update(
            $wpdb->posts,
            [
                'post_modified' => $post_post_modified_last,
                'post_modified_gmt' => $post_post_modified_last,
            ],
            [
                'ID' => $simple_post_id_last,
            ],
        );

        $wpdb->update(
            $wpdb->posts,
            [
                'post_modified' => $book_post_modified_first,
                'post_modified_gmt' => $book_post_modified_first,
            ],
            [
                'ID' => $book_cpt_id_first,
            ],
        );

        $wpdb->update(
            $wpdb->posts,
            [
                'post_modified' => $book_post_modified_last,
                'post_modified_gmt' => $book_post_modified_last,
            ],
            [
                'ID' => $book_cpt_id_last,
            ],
        );

        $this->assertSame($post_post_modified_last, get_lastpostmodified('blog', 'post'));
        $this->assertSame($book_post_modified_last, get_lastpostmodified('blog', 'book'));
    }
}
