<?php
declare(strict_types=1);

namespace App\Helpers;

class ResponseError
{

    public const NO_ERROR = 'NO_ERROR'; // 'OK'
    public const ERROR_100 = 'ERROR_100'; // 'User is not logged in.'
    public const ERROR_101 = 'ERROR_101'; // 'User does not have the right roles.'
    public const ERROR_102 = 'ERROR_102'; // 'Login or password is incorrect.'
    public const ERROR_103 = 'ERROR_103'; // 'User email address is not verified'
    public const ERROR_104 = 'ERROR_104'; // 'User phone number is not verified'
    public const ERROR_105 = 'ERROR_105'; // 'User account is not verified'
    public const ERROR_106 = 'ERROR_106'; // 'User already exists'
    public const ERROR_107 = 'ERROR_107'; // 'Please log in using socials'
    public const ERROR_108 = 'ERROR_108'; // 'User doesn't have Wallet'
    public const ERROR_109 = 'ERROR_109'; // 'Insufficient wallet balance'
    public const ERROR_110 = 'ERROR_110'; // 'Can't update this user role'
    public const ERROR_111 = 'ERROR_111'; // 'You can buy only :quantity products'
    public const ERROR_112 = 'ERROR_112'; // 'You can buy only :quantity products'
    public const ERROR_114 = 'ERROR_114'; // 'Seller doesn't have Wallet'
    public const ERROR_115 = 'ERROR_115'; // 'Phone not found'
    public const ERROR_116 = 'ERROR_116'; // 'Ads already activated'
    public const ERROR_117 = 'ERROR_117'; // 'Phone is required'
    public const ERROR_118 = 'ERROR_118'; // 'Already request'

    public const ERROR_201 = 'ERROR_201'; // 'Wrong OTP Code'
    public const ERROR_202 = 'ERROR_202'; // 'Too many request, try later'
    public const ERROR_203 = 'ERROR_203'; // 'OTP code is expired'

    public const ERROR_208 = 'ERROR_208'; // 'Subscription already active'
    public const ERROR_214 = 'ERROR_214'; // 'Your subscription product limit has expired'
    public const ERROR_215 = 'ERROR_215'; // 'Incorrect code or token expired'
    public const ERROR_216 = 'ERROR_216'; // 'Verify code sent'
    public const ERROR_217 = 'ERROR_217'; // 'email code sent'
    public const ERROR_218 = 'ERROR_218'; // 'not activated'
    public const ERROR_219 = 'ERROR_219'; // 'Your subscription is expired at'
    public const ERROR_220 = 'ERROR_220'; // 'Your subscription product limit has expired'
    public const ERROR_221 = 'ERROR_221'; // 'Your ads package limit has expired'
    public const ERROR_222 = 'ERROR_222'; // 'Can't attach product to this package'

    public const ERROR_252 = 'ERROR_252'; // 'Status already used'
    public const ERROR_253 = 'ERROR_253'; // 'Wrong status type'
    public const ERROR_254 = 'ERROR_254'; // 'Can't update Cancel status'
    public const ERROR_255 = 'ERROR_255'; // 'Can't update Order status if order already on a way or delivered'

    public const ERROR_400 = 'ERROR_400'; // 'Bad request'
    public const ERROR_401 = 'ERROR_401'; // 'UNAUTHORIZED'
    public const ERROR_403 = 'ERROR_403'; // 'Your project is not activated'
    public const ERROR_404 = 'ERROR_404'; // 'Item\'s not found.'
    public const ERROR_415 = 'ERROR_415'; // 'No connection to database'
    public const ERROR_422 = 'ERROR_422'; // 'Validation Error'
    public const ERROR_429 = 'ERROR_429'; // 'Too many requests'
    public const ERROR_430 = 'ERROR_430'; // 'Product quantity 0'
    public const ERROR_431 = 'ERROR_431'; // 'Active default currency not found'
    public const ERROR_432 = 'ERROR_432'; // 'Undefined Type'
    public const ERROR_434 = 'ERROR_434'; // 'Payment type must be only wallet or cash'
    public const ERROR_501 = 'ERROR_501'; // 'Error during created.'
    public const ERROR_502 = 'ERROR_502'; // 'Error during updated.'
    public const ERROR_503 = 'ERROR_503'; // 'Error during deleting.'
    public const ERROR_504 = 'ERROR_504'; // 'Can't delete record that has children.'
    public const ERROR_505 = 'ERROR_505'; // 'Can't delete default record.'
    public const ERROR_506 = 'ERROR_506'; // 'Already exists.'
    public const ERROR_507 = 'ERROR_507'; // 'Can't delete record that has products.'
    public const ERROR_508 = 'ERROR_508'; // 'Excel format incorrect or data invalid.'
    public const ERROR_509 = 'ERROR_509'; // 'Invalid date format.'

    public const NEW_ORDER = 'NEW_ORDER';
    public const CONFIRMATION_CODE = 'CONFIRMATION_CODE';
    public const PHONE_OR_EMAIL_NOT_FOUND = 'PHONE_OR_EMAIL_NOT_FOUND';
    public const ORDER_NOT_FOUND = 'ORDER_NOT_FOUND';
    public const NOT_IN_POLYGON = 'NOT_IN_POLYGON';
    public const CURRENCY_NOT_FOUND = 'CURRENCY_NOT_FOUND';
    public const LANGUAGE_NOT_FOUND = 'LANGUAGE_NOT_FOUND';
    public const CANT_DELETE_ORDERS = 'CANT_DELETE_ORDERS';
    public const CANT_UPDATE_ORDERS = 'CANT_UPDATE_ORDERS';
    public const STATUS_CHANGED = 'STATUS_CHANGED';
    public const ADD_CASHBACK = 'ADD_CASHBACK';
    public const WALLET_TOP_UP = 'WALLET_TOP_UP';
    public const WALLET_WITHDRAW = 'WALLET_WITHDRAW';
    public const CANT_DELETE_IDS = 'CANT_DELETE_IDS';
    public const USER_NOT_FOUND = 'USER_NOT_FOUND';
    public const USER_IS_BANNED = 'USER_IS_BANNED';
    public const INCORRECT_LOGIN_PROVIDER = 'INCORRECT_LOGIN_PROVIDER';
    public const FIN_FO = 'FIN_FO';
    public const USER_SUCCESSFULLY_REGISTERED = 'USER_SUCCESSFULLY_REGISTERED';
    public const PRODUCTS_IS_EMPTY = 'PRODUCTS_IS_EMPTY';
    public const RECORD_WAS_SUCCESSFULLY_CREATED = 'RECORD_WAS_SUCCESSFULLY_CREATED';
    public const RECORD_WAS_SUCCESSFULLY_UPDATED = 'RECORD_WAS_SUCCESSFULLY_UPDATED';
    public const RECORD_WAS_SUCCESSFULLY_DELETED = 'RECORD_WAS_SUCCESSFULLY_DELETED';
    public const IMAGE_SUCCESSFULLY_UPLOADED = 'IMAGE_SUCCESSFULLY_UPLOADED';
    public const EMPTY_STATUS = 'EMPTY_STATUS';
    public const SUCCESS = 'SUCCESS';
    public const CATEGORY_IS_PARENT = 'CATEGORY_IS_PARENT';
    public const TYPE_PRICE_USER = 'TYPE_PRICE_USER';
    public const NOTHING_TO_UPDATE = 'NOTHING_TO_UPDATE';
    public const EMPTY = 'EMPTY';
    public const CANT_UPDATE_EMPTY_ORDER = 'CANT_CREATE_OR_UPDATE_EMPTY_ORDER';
    public const YOUR_ADS_PACKAGE_LIMIT_HAS_EXPIRED = 'YOUR_ADS_PACKAGE_LIMIT_HAS_EXPIRED';
    public const CANT_ATTACH_PRODUCT_TO_THIS_PACKAGE = 'CANT_ATTACH_PRODUCT_TO_THIS_PACKAGE';
    public const WRONG_PRODUCT = 'WRONG_PRODUCT';
}
