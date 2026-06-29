<?php

namespace Tests\Unit\Biomarkers;

use App\Domain\Biomarkers\ResolveBiomarkerThemeCategory;
use Tests\TestCase;

class ResolveBiomarkerThemeCategoryTest extends TestCase
{
    public function test_resolves_common_cma_marker_names_to_vitasure_themes(): void
    {
        $resolver = new ResolveBiomarkerThemeCategory;

        $this->assertSame('Ontstekingen', $resolver->resolve('CRP hooggevoelig*'));
        $this->assertSame('Ontstekingen', $resolver->resolve('hsCRP'));
        $this->assertSame('Uithoudingsvermogen', $resolver->resolve('Ferritine'));
        $this->assertSame('Uithoudingsvermogen', $resolver->resolve('Hemoglobine'));
        $this->assertSame('Metabolisme', $resolver->resolve('Hemoglobine A1c (IFCC)*'));
        $this->assertSame('Hormoonbalans', $resolver->resolve('TSH'));
        $this->assertSame('Slaap', $resolver->resolve('Vitamine D'));
        $this->assertSame('Slaap', $resolver->resolve('Vitamin D'));
        $this->assertSame('Hartgezondheid', $resolver->resolve('LDL cholesterol'));
        $this->assertNull($resolver->resolve('Onbekende marker XYZ'));
    }
}
