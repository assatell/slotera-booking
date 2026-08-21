<?php
declare(strict_types=1);
namespace Slotera\Application\Services;
use Slotera\Application\Services\Translations\FrontendTranslationStrings;
use Slotera\Application\Services\Translations\EmailTranslationStrings;
if (!defined('ABSPATH')) { exit; }
final class TranslationFreezeService
{
    public const VERSION = '1.1.0';
    private array $manifest;
    public function __construct(?array $manifest=null){$this->manifest=$manifest ?? $this->loadManifest();}
    public function frozenLocales(): array{return array_values((array)($this->manifest['frozen_locales']??[]));}
    public function verify(string $section,string $locale): array
    {
        if(!in_array($locale,$this->frozenLocales(),true)){return ['valid'=>true,'frozen'=>false];}
        $expected=$this->manifest['sections'][$section][$locale]??null;
        if(!is_array($expected)){return ['valid'=>false,'frozen'=>true,'message'=>'Frozen locale baseline is missing.'];}
        $data=$this->sectionData($section,$locale);
        $actual=['items'=>$this->countItems($section,$data),'sha256'=>hash('sha256',$this->canonical($data))];
        $expectedItems=(int)($expected['items']??-1);
        $expectedHash=(string)($expected['sha256']??'');
        $valid=$expectedItems>0 && $actual['items']>0 && $expectedHash!=='' && hash_equals($expectedHash,(string)$actual['sha256']) && $expectedItems===(int)$actual['items'];
        return ['valid'=>$valid,'frozen'=>true,'expected'=>$expected,'actual'=>$actual,'message'=>$valid?'':'Frozen locale changed, is empty, or has an invalid baseline.'];
    }
    private function sourceLocale(string $section,string $locale): string
    {
        $sectionAliases=(array)($this->manifest['section_aliases'][$section]??[]);
        if(isset($sectionAliases[$locale])){return (string)$sectionAliases[$locale];}
        return (string)(($this->manifest['aliases'][$locale]??$locale));
    }
    private function sectionData(string $section,string $locale): array
    {
        $source=$this->sourceLocale($section,$locale);
        if($section==='frontend'){return $this->extract(FrontendTranslationStrings::strings(),$source);}
        if($section==='emails'){return $this->extract(EmailTranslationStrings::strings(),$source);}
        if($section==='email_templates'){$data=EmailTemplateRegistry::qa_translation_fields_for_locale($source);ksort($data);foreach($data as &$fields){ksort($fields);}unset($fields);return $data;}
        return [];
    }
    private function extract(array $catalog,string $locale): array{$out=[];foreach($catalog as $key=>$meta){$out[(string)$key]=(string)($meta[$locale]??'');}ksort($out);return $out;}
    private function countItems(string $section,array $data): int{if($section!=='email_templates'){return count($data);} $n=0;foreach($data as $fields){$n+=is_array($fields)?count($fields):0;}return $n;}
    private function canonical(array $data): string{return (string)json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRESERVE_ZERO_FRACTION);}
    private function loadManifest(): array{$file=dirname(__DIR__,2).'/config/translation-freeze.php';return is_file($file)?(array)require $file:[];}
}
