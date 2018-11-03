<?php

namespace Leochenftw\Extension;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SaltedHerring\Salted\Cropper\SaltedCroppableImage;
use SaltedHerring\Salted\Cropper\Fields\CroppableImageField;
use GuzzleHttp\Client;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;
use SilverStripe\AssetAdmin\Controller\AssetAdmin;

use Leochenftw\Model\FacebookProfile;
use Leochenftw\Model\LinkedinProfile;
use Leochenftw\Model\WechatProfile;

class MemberExtension extends DataExtension
{
    private static $has_one = [
        'Portrait'      =>  SaltedCroppableImage::class
    ];

    private static $cascade_deletes = [
        'Portrait'
    ];

    /**
     * Belongs_to relationship
     * @var array
     */
    private static $belongs_to = [
        'FacebookProfile'   =>  FacebookProfile::class,
        'LinkedinProfile'   =>  LinkedinProfile::class,
        'WechatProfile'     =>  WechatProfile::class
    ];

    /**
     * Update Fields
     * @return FieldList
     */
    public function updateCMSFields(FieldList $fields)
    {
        $owner = $this->owner;

        $fields->addFieldToTab(
            'Root.Main',
            CroppableImageField::create('PortraitID', 'Portrait')->setCropperRatio(1),
            'FirstName'
        );

        return $fields;
    }

    public function create_portrait($src, $filename, $write = true)
    {
        $fold           =   Folder::find_or_make('MemberPortraits');
        $client         =   new Client();
        $image          =   $client->request('GET', $src);
        $image          =   $image->getBody()->getContents();

        $img            =   Image::create();
        $img->setFromString($image, $filename);
        $img->ParentID  =   $fold->ID;
        $img->write();
        AssetAdmin::create()->generateThumbnails($img);
        $img->publishSingle();

        $croppable      =   SaltedCroppableImage::create();
        $croppable->OriginalID  =   $img->ID;
        $croppable->write();

        $this->owner->PortraitID   =   $croppable->ID;

        if ($write) {
            $this->owner->write();
        }
    }

    /**
     * Event handler called before deleting from the database.
     */
    public function onBeforeDelete()
    {
        parent::onBeforeDelete();

        if ($this->owner->FacebookProfile()->exists()) {
            $this->owner->FacebookProfile()->delete();
        }

        if ($this->owner->LinkedinProfile()->exists()) {
            $this->owner->LinkedinProfile()->delete();
        }
    }
}
