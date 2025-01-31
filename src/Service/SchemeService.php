<?php

namespace App\Service;

use App\Repository\Therapy\LabelRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

class SchemeService
{

  private $labelRepository;
  private $translator;

  public function __construct(LabelRepository $labelRepository, TranslatorInterface $translator)
  {
    $this->labelRepository = $labelRepository;
    $this->translator = $translator;
  }

  public function generateTbody(
    $selectedLabels,
    $suppress,
    $currentComments,
    $notCheckedCheckboxes,
    $excerpt,
    $currentLanguage
  ): string {
    $newTbody = '';

    foreach ($selectedLabels as $labelID) {
      $label = $this->labelRepository->find($labelID);
      $labelStubs = $label->getStubs();

      $trClass = 'table-light hideLabels';
      if ($suppress) {
        $trClass .= ' d-none';
      }
      $newTbody .= '<tr class="' . $trClass . '" id="rowLabel|' . $label->getId() . '">' .
        '<th colspan="5">' . $label->getShortName() . '</th>' .
        '</tr>';

      if (count($labelStubs) > 0) {
        foreach ($labelStubs as $stub) {
          // * Start Text Area
          $textAreaKey = 'labelID=' . $label->getId() . '|stubID=' . $stub->getId();
          $textAreaValue = '';
          foreach ($currentComments as $comment) {
            if ($comment['key'] == $textAreaKey) {
              $textAreaValue = $comment['comment'];
            }
          }
          $textArea = '<textarea name="' . $textAreaKey . '" class="form-control">' . $textAreaValue . '</textarea>';
          // * End Text Area

          // * Start Checkbox
          $inputKey = 'targets|labelID=' . $label->getId() . '|stubID=' . $stub->getId();

          $checked = 'checked';
          foreach ($notCheckedCheckboxes as $checkboxKey) {
            if ($checkboxKey == $inputKey) {
              $checked = '';
            }
          }
          $checkboxInput = '<input type="checkbox" name="' . $inputKey . '" class="form-check-input" ' . $checked . ' />';
          // * End Checkbox

          $descriptionItemClass = 'col-3 descriptionItem';
          $excerptItemClass = 'col-3 excerptItem';
          if ($excerpt) {
            $descriptionItemClass .= ' d-none';
          } else {
            $excerptItemClass .= ' d-none';
          }
          $newTbody .= '<tr id="rowLabel|' . $label->getId() . '|stub|' . $stub->getId() . '">' .
            '<td class="text-end col-2">' .
            $checkboxInput .
            '</td>' .
            '<td class="col-2">' .
            $stub->getName() .
            '</td>' .
            '<td class="' . $descriptionItemClass . '">' .
            $stub->getDescription() .
            '</td>' .
            '<td class="' . $excerptItemClass . '">' .
            $stub->getExcerpt() .
            '</td>' .
            '<td class="col-3">' .
            $stub->getBackground() .
            '</td>' .
            '<td class="border-start">' .
            $textArea .
            '</td>' .
            '</tr>';
        }
      } else {
        $translatedNoSavedTemplates = $this->translator->trans('app-therapy-report-no-saved-templates', [], 'messages', $currentLanguage);
        $newTbody .= '<tr id="rowLabel|' . $label->getId() . '|stub|0">' .
          '<th style="">' .
          '<span class="d-flex justify-content-center fs-4 text-secondary">' .
          $translatedNoSavedTemplates .
          '</span>' .
          '</th>' .
          '</tr>';
      }
    }

    return $newTbody;
  }

  public function generatePDFReport(
    $selectedLabels,
    $suppress,
    $currentComments,
    $notCheckedCheckboxes,
    $excerpt,
  ): string {

    $newTbody = '';

    foreach ($selectedLabels as $labelID) {
      $label = $this->labelRepository->find($labelID);
      $labelStubs = $label->getStubs();

      $trLabel = '';
      if (!$suppress) {
        $trLabel .= '<tr class="table-light" id="rowLabel|' . $label->getId() . '">' .
          '<th colspan="5">' . $label->getShortName() . '</th>' .
          '</tr>';
      }
      $newTbody .= $trLabel;

      foreach ($labelStubs as $stub) {

        // todo: dont show the line, if checkbox is not checked and show comments in a new row
        // // * Start Checkbox
        $inputKey = 'targets|labelID=' . $label->getId() . '|stubID=' . $stub->getId();
        // check $inputKey if exist in $notCheckedCheckboxes
        $exists = false;
        foreach ($notCheckedCheckboxes as $checkboxKey) {
          if ($checkboxKey == $inputKey) {
            $exists = true;
          }
        }

        if ($exists) {
          continue;
        }

        $descriptionExcerptItem = '';
        if ($excerpt) {
          $descriptionExcerptItem = '<td ></td><td>' .
            $stub->getExcerpt() .
            '</td>';
        } else {
          // 100px;
          $descriptionExcerptItem = '<td ></td><td>' .
            $stub->getDescription() .
            '</td>';
        }


        $newTbody .= '<tr id="rowLabel|' . $label->getId() . '|stub|' . $stub->getId() . '">' .
          '<td class="col-2">' .
          $stub->getName() .
          '</td>' .
          $descriptionExcerptItem .
          '</tr>';

        // Comment Section
        $commentKey = 'labelID=' . $label->getId() . '|stubID=' . $stub->getId();

        $comment = '';
        foreach ($currentComments as $currentComment) {
          if ($currentComment['key'] == $commentKey) {
            $comment = $currentComment['comment'];
          }
        }
        if ($comment != '') {
          $newTbody .= '<tr><td colspan="3">' . $comment . '</td></tr>';
        }
      }
    }
    return $newTbody;
  }
}
