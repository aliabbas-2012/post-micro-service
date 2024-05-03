<?php

namespace App\Traits;

/**
 * Description of CommonTrait
 *
 * @author rizwan
 */
trait MessageTrait {

    /**
     * Error messages
     * @var type 
     */
    public $errors = [
        'generalError' => 'Oops! someting went wrong please try again',
        'loginSessionExpired' => 'Sorry! your session has expired',
        'noUserFound' => 'No active users found',
        'usernameExists' => 'This username is taken! Try a different one',
        'adminBlockUser' => 'User is blocked',
        'profileNotFound' => 'User not found',
        'peopleNotFound' => 'No users found',
        'reportError' => 'Something went wrong while reporting ',
        'selfFollow' => 'You can not follow to yourself',
        'noRequestFound' => 'Request already cancelled or nobody sent you friend request',
        'rejectRequest' => 'Friend request already rejected or nobody sent you friend request',
        'confirmedRequest' => 'Request alredy confirmed or nobody sent you friend request',
        'noUserFriendList' => 'User not found in you friend list',
        'friendNotFound' => 'Friend user not found',
        'codeVerificationFailed' => 'Code verification failed',
        'passwordLimitExceed' => 'Password change limit exceeded,Please try later',
        'privacyUpdateError' => 'User privacy updation failed',
        'noBoxFound' => 'Box is not available anymore',
        'noBookmarkFound' => 'Your favourite list is empty',
        'userInfoMiss' => 'User information missing',
        'blockUserNorFound' => 'Blocked user not found',
        'selfBlockError' => 'You can not block to yourself',
        'alreadyUnblocked' => 'User already unblocked',
        'alreadyBlocked' => 'You already blocked this user',
        'noFriendRequest' => 'Friend request already cancelled or confirmed by user',
        'boxEditPermission' => 'Box permission issue or you are not allowed to edit box',
        'boxDelPermission' => 'Box permission issue or you are not allowed to delete this box',
        'updationError' => 'Something went wrong while updation',
        'postCommentPermissonError' => 'You are not allowed to comment on this post.',
        'commentDelePermissonError' => 'You are not authorized to delete this comment.',
        'postDelPermission' => 'You are not authorized to delete this post.',
        'postViewPermission' => 'You are not allowed to view this post.',
        'postViewUpgrade' => 'In order to view this post, please upgrade your app.',
        'postNotFound' => 'This Post is not available anymore!',
        'postNotAvailable' => 'Sorry, This post is not available anymore',
        'noResultFound' => 'No Result Found',
        'boxViewPermissionError' => 'You have no permission to view posts of this box',
        'paramInfoMiss' => 'Parameters information missing',
        // inputs errors
        'comment_id_required' => 'comment information is missing',
        'last_id_required' => 'Oops! something went wrong please try again',
        'post_id_required' => 'Post information is missing',
        'comment_required' => 'Comment content missing',
        'type_required' => "Load more type missing",
        'type_in' => "Invalid load more type",
        'last_id_required' => "Load more information missing",
        'last_id_integer' => "Invalid last comment information",
        'last_id_min' => "Invalid last comment information",
        'limit_required' => 'Limit information is missing',
        'offset_required' => 'Offset information is missing',
        "profile_id_required" => "User information missing",
        "less_than_required" => "Load more information missing",
        "less_than_integer" => "Invalid load more information",
        "less_than_min" => "Invalid load more information",
        "offset_integer" => "Invalid load more information",
        "offset_min" => "Invalid load more information",
        'unauthorized' => "Unauthorized",
    ];

    /**
     * Success messages
     * @var type 
     */
    public $success = [
        'generalSuccess' => 'Process successfully completed',
        'profileUpdate' => 'Profile updated successfully',
        'reportSuccess' => 'Reported successfully',
        'deviceTokenUpdate' => 'Device token saved successfully',
        'logout' => 'User logout successfully',
        'notiSettingsUpdate' => 'Notification settings updated successfully',
        'requestCanceled' => 'Friend request cancelled successfully',
        'friendRemoved' => 'User removed from your friend list successfully',
        'verifyCode' => 'Your verificaton code has been verified successfully',
        'feedback' => 'Thank you for getting in touch!',
        'messagePrivacuUpdate' => 'Message privacy updated successfully',
        'userPrivacyUpdate' => 'User privacy updated successfully',
        'bookmark' => 'Added in your favourite list successfully',
        'removeBookmark' => 'Removed from your favourite list successfully',
        'phoneUpdate' => 'Phone number updated successfully',
        'userBlocked' => 'User blocked successfully',
        'userUnblocked' => 'User unblocked successfully',
        'boxCreate' => 'Box created successfully',
        'boxUpdate' => 'Box updated successfully',
        'boxDelete' => 'Box deleted successfully',
        'postDelete' => 'Post deleted successfully',
        'emailUpdate' => 'Email update successfully',
        'phoneUpdate' => 'Phone number changed successfully',
        'passwordUpdate' => 'Password changed successfully',
        'commentDelete' => 'Comment deleted successfully',
        'deprecated' => 'This Application version is Deprecated,Please upgrade your app',
    ];

}
