<?php

declare(strict_types=1);

namespace B7s\FluentVision\Services\Providers;

use function array_filter;
use function implode;

class OptionBuilder
{
    /**
     * @param  array<string, mixed>  $options
     * @param  array<int, string>  $args
     */
    public static function appendFloatOption(array $options, array &$args, string $key, string $flag): void
    {
        if (isset($options[$key]) && (is_float($options[$key]) || is_int($options[$key]))) {
            $args[] = $flag;
            $args[] = (string) $options[$key];
        }
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<int, string>  $args
     */
    public static function appendIntOption(array $options, array &$args, string $key, string $flag): void
    {
        if (isset($options[$key]) && is_int($options[$key])) {
            $args[] = $flag;
            $args[] = (string) $options[$key];
        }
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<int, string>  $args
     */
    public static function appendStringOption(array $options, array &$args, string $key, string $flag): void
    {
        if (isset($options[$key]) && is_string($options[$key]) && $options[$key] !== '') {
            $args[] = $flag;
            $args[] = $options[$key];
        }
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<int, string>  $args
     */
    public static function appendBoolOption(array $options, array &$args, string $key, string $flag): void
    {
        if (isset($options[$key]) && $options[$key] === true) {
            $args[] = $flag;
        }
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<int, string>  $args
     */
    public static function appendClassesOption(array $options, array &$args): void
    {
        if (isset($options['classes']) && is_array($options['classes']) && $options['classes'] !== []) {
            $args[] = '--classes';
            $classes = array_filter($options['classes'], is_string(...));
            $args[] = implode(',', $classes);
        }
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<int, string>  $args
     */
    public static function appendPromptsOption(array $options, array &$args): void
    {
        if (isset($options['prompts']) && is_array($options['prompts']) && $options['prompts'] !== []) {
            $args[] = '--prompts';
            $prompts = array_filter($options['prompts'], is_string(...));
            $args[] = implode(',', $prompts);
        }
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<int, string>  $args
     */
    public static function appendCommonOptions(array $options, array &$args): void
    {
        self::appendFloatOption($options, $args, 'conf', '--conf');
        self::appendIntOption($options, $args, 'imgsz', '--imgsz');
        self::appendBoolOption($options, $args, 'save', '--save');
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<int, string>  $args
     */
    public static function appendVideoOptions(array $options, array &$args): void
    {
        self::appendFloatOption($options, $args, 'conf', '--conf');
        self::appendFloatOption($options, $args, 'iou', '--iou');
        self::appendIntOption($options, $args, 'imgsz', '--imgsz');
        self::appendIntOption($options, $args, 'vid_stride', '--vid-stride');
        self::appendBoolOption($options, $args, 'save', '--save');
    }
}
