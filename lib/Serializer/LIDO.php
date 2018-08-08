<?php

/**
 * This file is part of HAB VKK OAI-PMH.
 *
 * HAB VKK OAI-PMH is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * HAB VKK OAI-PMH is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with HAB VKK OAI-PMH.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    David Maus <maus@hab.de>
 * @copyright (c) 2017, 2018 by Herzog August Bibliothek Wolfenbüttel
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License v3 or higher
 */

namespace HAB\VKK\Service\Export\Serializer;

use HAB\VKK\Entity\Ortrolle;
use HAB\VKK\Entity\Ort;
use HAB\VKK\Entity\Personrolle;
use HAB\VKK\Entity\Person;
use HAB\VKK\Entity\Blatt;

use DOMText;
use DOMElement;
use DOMDocument;

/**
 * Serialize blatt entity to LIDO.
 *
 * @author    David Maus <maus@hab.de>
 * @copyright (c) 2017, 2018 by Herzog August Bibliothek Wolfenbüttel
 * @license   http://www.gnu.org/licenses/gpl.txt GNU General Public License v3 or higher
 */
class LIDO implements SerializerInterface
{
    private static $placeAuthCodes = ['tgn', 'gnd'];
    private static $personAuthCodes = ['ulan', 'gnd'];

    public function serialize (Blatt $blatt)
    {

        $document = new DOMDocument();
        $record = $document->appendChild($document->createElementNS('http://www.lido-schema.org', 'lido:lido'));
        $record->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:schemaLocation', 'http://www.lido-schema.org http://www.lido-schema.org/schema/v1.0/lido-v1.0.xsd');

        $this->append($record, 'lido:lidoRecID', ['lido:type' => 'local'], sprintf('lido:virtuelles-kupferstichkabinett.de:%d', $blatt->getId()));

        $this->serializeDescriptiveMetadata($record, $blatt);
        $this->serializeAdministrativeMetadata($record, $blatt);

        return $document->saveXml($document->documentElement);
    }

    protected function serializeDescriptiveMetadata (DOMElement $record, Blatt $blatt)
    {
        $descriptiveMetadata = $this->append($record, 'lido:descriptiveMetadata', ['xml:lang' => 'de']);
        $this->serializeObjectClassification($descriptiveMetadata, $blatt);
        $this->serializeObjectIdentification($descriptiveMetadata, $blatt);
        $this->serializeEvents($descriptiveMetadata, $blatt);
        $this->serializeObjectRelations($descriptiveMetadata, $blatt);
    }

    protected function serializeEvents (DOMElement $descriptiveMetadata, Blatt $blatt)
    {
        $eventWrap = $this->append($descriptiveMetadata, 'lido:eventWrap');

        $eventSet = $this->append($eventWrap, 'lido:eventSet');
        $event = $this->append($eventSet, 'lido:event');
        $this->append(
            $this->append($event, 'lido:eventType'), 'lido:term', null, 'Erschaffung/Herstellung'
        );
        foreach ($blatt->getPersonrolle() as $rolle) {
            if ($rolle->getFunktion()->getEreignis() === 'Erschaffung') {
                $this->serializeEventActor($event, $rolle);
            }
        }
        if ($schule = $blatt->getSchule()) {
            $this->append(
                $this->append($event, 'lido:culture'), 'lido:term', null, $schule->getText()
            );
        }
        if ($kultur = $blatt->getKultur()) {
            $this->append(
                $this->append($event, 'lido:culture'), 'lido:term', null, $kultur
            );
        }

        $dateMin = $blatt->getDatierungF();
        $dateMax = $blatt->getDatierungP();
        if ($dateMin or $dateMax) {
            $date = $this->append(
                $this->append($event, 'lido:eventDate'), 'lido:date'
            );

            if ($dateMin > $dateMax) {
                list($dateMin, $dateMax) = array($dateMax, $dateMin);
            }
            if ($dateMin and $dateMax) {
                $this->append($date, 'lido:earliestDate', null, $dateMin);
                $this->append($date, 'lido:latestDate', null, $dateMax);
            } else {
                $dateMin = $dateMin ?: $dateMax;
                $this->append($date, 'lido:earliestDate', null, $dateMin);
                $this->append($date, 'lido:latestDate', null, $dateMin);
            }
        }

        foreach ($blatt->getOrtrolleByEreignis('Erschaffung') as $rolle) {
            $this->serializeEventPlace($event, $rolle);
        }

        $materialsTech = $this->append(
            $this->append($event, 'lido:eventMaterialsTech'), 'lido:materialsTech'
        );
        foreach ($blatt->getTechnik() as $technik) {
            $this->append(
                $this->append($materialsTech, 'lido:termMaterialsTech', ['lido:type' => 'technique']),
                'lido:term', null, $technik->getText()
            );
        }
        foreach ($blatt->getDrucktechnik() as $technik) {
            $this->append(
                $this->append($materialsTech, 'lido:termMaterialsTech', ['lido:type' => 'printing technique']),
                'lido:term', null, $technik->getText()
            );
        }
        if ($material = $blatt->getTraeger()) {
            $this->append(
                $this->append($materialsTech, 'lido:termMaterialsTech', ['lido:type' => 'material']),
                'lido:term', null, $material->getText()
            );
        }

        // Frühere Besitzer
        $besitzer = array();
        foreach ($blatt->getPersonrolle() as $rolle) {
            if ($rolle->getFunktion()->getEreignis() === 'Besitz') {
                $besitzer []= $rolle;
            }
        }
        if (!empty($besitzer)) {
            foreach ($besitzer as $rolle) {
                $eventSet = $this->append($eventWrap, 'lido:eventSet');
                $event = $this->append($eventSet, 'lido:event');
                $this->append(
                    $this->append($event, 'lido:eventType'), 'lido:term', null, 'Besitz'
                );
                $this->serializeEventActor($event, $rolle);
            }
        }


        // Ausstellung
        if ($ausstellung = $blatt->getAusstellung()) {
            $eventSet = $this->append($eventWrap, 'lido:eventSet');
            $this->append($eventSet, 'lido:displayEvent', null, $ausstellung);
            $event = $this->append($eventSet, 'lido:event');
            $this->append(
                $this->append($event, 'lido:eventType'), 'lido:term', null, 'Ausstellung'
            );
        }
    }

    protected function serializeEventPlace (DOMElement $event, $rolle)
    {
        $eventPlace = $this->append($event, 'lido:eventPlace');
        $this->serializePlace($eventPlace, $rolle->getOrt());
    }

    protected function serializePlace (DOMElement $parent, Ort $ort)
    {
        $place = $this->append($parent, 'lido:place');
        foreach (explode(';', $ort->getTgn()) as $authnum) {

            $authnum = trim($authnum);
            if (strpos($authnum, '_') === false) {
                $authnum = "tgn_{$authnum}";
            }
            if (preg_match('/^[a-z]+_[a-z0-9\-]+$/ui', $authnum)) {
                list($source, $number) = explode('_', $authnum, 2);
                if (in_array($source, self::$placeAuthCodes)) {
                    $this->append($place, 'lido:placeID', ['lido:type' => 'code', 'lido:source' => $source], $number);
                }
            }
        }
        $this->append(
            $this->append($place, 'lido:namePlaceSet'), 'lido:appellationValue', null, $ort->getText()
        );
    }

    protected function serializeEventActor (DOMElement $event, $rolle)
    {
        $eventActor = $this->append($event, 'lido:eventActor');
        $actorInRole = $this->append($eventActor, 'lido:actorInRole');
        $this->serializeActor($actorInRole, $rolle->getPerson());
        $this->append(
            $this->append($actorInRole, 'lido:roleActor'), 'lido:term', null, $rolle->getFunktion()->getText()
        );
        if ($qualifier = $rolle->getQualifier()) {
            $this->append($actorInRole, 'lido:attributionQualifierActor', null, $qualifier);
        }
    }

    protected function serializeActor (DOMElement $parent, Person $person)
    {
        $actor = $this->append($parent, 'lido:actor');
        foreach (explode(';', $person->getGnd()) as $authnum) {
            if (preg_match('/^[a-z]+_[a-z0-9\-]+$/ui', trim($authnum))) {
                list($source, $number) = explode('_', trim($authnum), 2);
                if (in_array($source, self::$personAuthCodes)) {
                    $this->append($actor, 'lido:actorID', ['lido:type' => 'code', 'lido:source' => $source], $number);
                }
            }
        }
        $this->append(
            $this->append($actor, 'lido:nameActorSet'), 'lido:appellationValue', null, $person->getText()
        );
    }

    protected function serializeObjectClassification (DOMElement $descriptiveMetadata, Blatt $blatt)
    {
        $objectClassificiationWrap = $this->append($descriptiveMetadata, 'lido:objectClassificationWrap');
        $objectWorkTypeWrap = $this->append($objectClassificiationWrap, 'lido:objectWorkTypeWrap');
        $objectWorkType = $this->append($objectWorkTypeWrap, 'lido:objectWorkType');

        $workTypeId = $blatt->getObjectWorkTypeCode();
        $workTypeTerm =$blatt->getObjectWorkTypeTerm();

        $this->append($objectWorkType, 'lido:conceptID', ['lido:type' => 'code', 'lido:source' => 'aat'], $workTypeId);
        $this->append($objectWorkType, 'lido:term', null, $workTypeTerm);

        if ($workTypeTerm != $blatt->getObjektStatus()) {
            $objectWorkType = $this->append($objectWorkTypeWrap, 'lido:objectWorkType');
            $this->append($objectWorkType, 'lido:term', null, $blatt->getObjektStatus());
        }
    }

    protected function serializeObjectRelations (DOMElement $descriptiveMetadata, Blatt $blatt)
    {
        $objectRelationWrap = $this->append($descriptiveMetadata, 'lido:objectRelationWrap');
        $this->serializeSubjects($objectRelationWrap, $blatt);
        $relatedWorksWrap = $this->append($objectRelationWrap, 'lido:relatedWorksWrap');
        foreach ($blatt->getHandbuchreferenz() as $ref) {
            if ($ref->getSortkey() > 0) {
                $attrs = ['lido:sortorder' => $ref->getSortkey()];
            } else {
                $attrs = null;
            }
            $relatedWorkSet = $this->append($relatedWorksWrap, 'lido:relatedWorkSet', $attrs);
            $displayObject = $ref->getHandbuch()->getText();
            if ($ref->getText()) {
                $displayObject .= ", S. {$ref->getText()}";
            }
            $this->append(
                $this->append($relatedWorkSet, 'lido:relatedWork'), 'lido:displayObject', null, $displayObject
            );
            $this->append(
                $this->append($relatedWorkSet, 'lido:relatedWorkRelType'), 'lido:term', null, 'beschrieben in'
            );
        }

        foreach ($blatt->getLiteraturreferenz() as $ref) {
            $relatedWorkSet = $this->append($relatedWorksWrap, 'lido:relatedWorkSet', null);
            $displayObject = $ref->getLiteratur()->getText();
            if ($ref->getSeite()) {
                $displayObject .= ", S. {$ref->getSeite()}";
            }
            $this->append(
                $this->append($relatedWorkSet, 'lido:relatedWork'), 'lido:displayObject', null, $displayObject
            );
            $this->append(
                $this->append($relatedWorkSet, 'lido:relatedWorkRelType'), 'lido:term', null, 'beschrieben in'
            );
        }

        if ($blatt->getSerie()) {

            $relatedWorkSet = $this->append($relatedWorksWrap, 'lido:relatedWorkSet');
            $displayObject = $blatt->getSerie();
            $this->append(
                $this->append($relatedWorkSet, 'lido:relatedWork'), 'lido:displayObject', null, $displayObject
            );
            $this->append(
                $this->append($relatedWorkSet, 'lido:relatedWorkRelType'), 'lido:term', null, 'Teil von'
            );
        }
        if ($blatt->getVorlage()) {
            $relatedWorkSet = $this->append($relatedWorksWrap, 'lido:relatedWorkSet');
            $displayObject = $blatt->getVorlage();
            if ($blatt->getVorlagestandort()) {
                $displayObject = "{$displayObject} ({$blatt->getVorlagestandort()})";
            }
            $this->append(
                $this->append($relatedWorkSet, 'lido:relatedWork'), 'lido:displayObject', null, $displayObject
            );
            $this->append(
                $this->append($relatedWorkSet, 'lido:relatedWorkRelType'), 'lido:term', null, 'hat Vorlage'
            );

        }
    }

    protected function serializeSubjects (DOMElement $objectRelationWrap, Blatt $blatt)
    {
        $subjectWrap = $this->append($objectRelationWrap, 'lido:subjectWrap');
        $concepts = $blatt->getSchlagwort();
        if (count($concepts) > 0) {
            $subjectSet = $this->append($subjectWrap, 'lido:subjectSet');
            $this->append($subjectSet, 'lido:displaySubject', null, 'Schlagwörter');
            $subject = $this->append($subjectSet, 'lido:subject');
            foreach ($concepts as $concept) {
                $this->append(
                    $this->append($subject, 'lido:subjectConcept'), 'lido:term', null, $concept->getText()
                );
            }
        }

        $concepts = $blatt->getClassifications();
        if (count($concepts) > 0) {
            $subjectSet = $this->append($subjectWrap, 'lido:subjectSet');
            $this->append($subjectSet, 'lido:displaySubject', null, 'Klassifikation der Bildinhalte');
            $subject = $this->append($subjectSet, 'lido:subject');
            foreach ($concepts as $concept) {
                $subjectConcept = $this->append($subject, 'lido:subjectConcept');
                $this->append($subjectConcept, 'lido:conceptID', ['lido:type' => 'code', 'lido:source' => 'iconclass'], $concept->getNotation());
                if ($label = trim($concept->getDescription())) {
                    $this->append($subjectConcept, 'lido:term', null, $label);
                }
                foreach (preg_split('/[,;]/u', $concept->getKeywords()) as $keyword) {
                    if ($keyword = trim($keyword)) {
                        $this->append($subjectConcept, 'lido:term', ['lido:addedSearchTerm' => 'yes'], $keyword);
                    }
                }
            }
        }

        $actors = array();
        foreach ($blatt->getPersonrolle() as $personrolle) {
            if ($personrolle->getFunktion()->getAnzeige() === 'Dargestellte Person') {
                $actors []= $personrolle->getPerson();
            }
        }
        if ($actors) {
            $subjectSet = $this->append($subjectWrap, 'lido:subjectSet');
            $this->append($subjectSet, 'lido:displaySubject', null, 'Dargestellte Personen');
            $subject = $this->append($subjectSet, 'lido:subject');
            foreach ($actors as $actor) {
                $this->serializeActor(
                    $this->append($subject, 'lido:subjectActor'), $actor
                );
            }
        }

        $places = array();
        foreach ($blatt->getOrtrolle() as $ortrolle) {
            if ($ortrolle->getFunktion()->getAnzeige() === 'Dargestellter Ort') {
                $places []= $ortrolle->getOrt();
            }
        }
        if ($places) {
            $subjectSet = $this->append($subjectWrap, 'lido:subjectSet');
            $this->append($subjectSet, 'lido:displaySubject', null, 'Dargestellte Orte');
            $subject = $this->append($subjectSet, 'lido:subject');
            foreach ($places as $place) {
                $this->serializePlace(
                    $this->append($subject, 'lido:subjectPlace'), $place
                );
            }
        }

        $objects = array();
        foreach ($blatt->getOrtrolle() as $ortrolle) {
            if ($ortrolle->getFunktion()->getAnzeige() === 'Dargestelltes Bauwerk') {
                $objects []= $ortrolle->getOrt();
            }
        }
        if ($objects) {
            $subjectSet = $this->append($subjectWrap, 'lido:subjectSet');
            $this->append($subjectSet, 'lido:displaySubject', null, 'Dargestellte Bauwerke');
            $subject = $this->append($subjectSet, 'lido:subject');
            foreach ($objects as $object) {

                $authcodes = [];
                $subjectObject = $this->append($subject, 'lido:subjectObject');
                foreach (explode(';', $object->getTgn()) as $authnum) {

                    $authnum = trim($authnum);
                    if (strpos($authnum, '_') === false) {
                        $authnum = "tgn_{$authnum}";
                    }
                    if (preg_match('/^[a-z]+_[a-z0-9\-]+$/ui', $authnum)) {
                        list($source, $number) = explode('_', $authnum, 2);
                        if (in_array($source, self::$placeAuthCodes)) {
                            $authcodes []= compact('source', 'number');
                        }
                    }
                }
                if ($authcodes) {
                    $objectElement = $this->append($subjectObject, 'lido:object');
                    foreach ($authcodes as $authcode) {
                        $this->append($objectElement, 'lido:objectID', ['lido:type' => 'code', 'lido:source' => $authcode['source']], $authcode['number']);
                    }
                }
                $this->append($subjectObject, 'lido:displayObject', null, $object->getText());
            }
        }
    }

    protected function serializeObjectIdentification (DOMElement $descriptiveMetadata, Blatt $blatt)
    {
        $objectIdentificationWrap = $this->append($descriptiveMetadata, 'lido:objectIdentificationWrap');
        $titleWrap = $this->append($objectIdentificationWrap, 'lido:titleWrap');
        $titleSet = $this->append($titleWrap, 'lido:titleSet');

        $prefTitle = sprintf('%s %s', $blatt->getObjektStatus(), $blatt->getSignatur());
        $this->append($titleSet, 'lido:appellationValue', null, $prefTitle);

        if ($altTitle = trim($blatt->getTitel())) {
            $titleSet = $this->append($titleWrap, 'lido:titleSet');
            $this->append($titleSet, 'lido:appellationValue', null, $altTitle);
        }

        if ($blatt->getBlattnr() and $blatt->getSerie()) {
            $title = sprintf('%s - Blatt %d', $blatt->getSerie(), $blatt->getBlattnr());
            $titleSet = $this->append($titleWrap, 'lido:titleSet');
            $this->append($titleSet, 'lido:appellationValue', null, $title);
        }

        $inscriptionsWrap = $this->append($objectIdentificationWrap, 'lido:inscriptionsWrap');
        if ($inschrift = $blatt->getInschrift()) {
            $inscriptions = $this->append(
                $this->append($inscriptionsWrap, 'lido:inscriptions', ['lido:type' => 'inscription']), 'lido:inscriptionTranscription', [], $inschrift
            );
        }

        $repositoryWrap = $this->append($objectIdentificationWrap, 'lido:repositoryWrap');
        $repositorySet = $this->append($repositoryWrap, 'lido:repositorySet', ['lido:type' => 'current']);
        $this->appendInstitution(
            $this->append($repositorySet, 'lido:repositoryName'),  $blatt->getInstitutionCode()
        );
        $this->append($repositorySet, 'lido:workID', null, $blatt->getSignatur());

        if ($alteInvNr = $blatt->getAlteInvnr()) {
            foreach (explode(';', $alteInvNr) as $invNr) {
                if ($invNr = trim($invNr)) {
                    $repositorySet = $this->append($repositoryWrap, 'lido:repositorySet', ['lido:type' => 'former']);
                    $this->appendInstitution(
                        $this->append($repositorySet, 'lido:repositoryName'),  $blatt->getInstitutionCode()
                    );
                    $this->append($repositorySet, 'lido:workID', null, $invNr);
                }
            }
        }

        if ($anmerkungen = $blatt->getAnmerkungen()) {
            $objectDescriptionWrap = $this->append($objectIdentificationWrap, 'lido:objectDescriptionWrap');
            $this->append(
                $this->append($objectDescriptionWrap, 'lido:objectDescriptionSet'), 'lido:descriptiveNoteValue', [], $anmerkungen
            );
        }

        $objectMeasurementsWrap = $this->append($objectIdentificationWrap, 'lido:objectMeasurementsWrap');

        if ($blatt->getPlatteh() or $blatt->getPlatteh()) {
            // Werte für die Maße von Druckgraphik und Zeichnungen fließen
            // bei der Erfassung in zwei Listen zusammen; Problem ist,
            // dass die Logik der Zuordnung gegenläufig ist; bei der
            // Druckgraphik ist das Maß "Platte" das kleinere, das Maß
            // "Blatt" das größere, bei den Zeichnungen ist das Maß
            // "Zeichnung" bzw. "Blatt" jedoch das kleinere, ... . Die
            // Werte können also nicht analog zugeordnet werden, sondern
            // müssen gegenläufig zugeordnet werden, allein abhängig vom
            // Objekttyp "Druckgraphik" bzw. "Zeichnung".
            //
            // Oh je...
            if ($blatt->isZeichnung()) {
                if ($blatt->getInstitution() === 2) {
                    $label = 'Montierung';
                } else {
                    $label = 'Zeichnung';
                }
            } else {
                $label = 'Platte';
            }
            $objectMeasurementsSet = $this->append($objectMeasurementsWrap, 'lido:objectMeasurementsSet');
            $objectMeasurements = $this->append($objectMeasurementsSet, 'lido:objectMeasurements');
            if ($blatt->getDurchmesser()) {
                $measurementsSet = $this->append($objectMeasurements, 'lido:measurementsSet');
                $this->append($measurementsSet, 'lido:measurementType', [], 'Durchmesser');
                $this->append($measurementsSet, 'lido:measurementUnit', [], 'mm');
                $this->append($measurementsSet, 'lido:measurementValue', [], $blatt->getPlatteh());
                $this->append($objectMeasurements, 'lido:shapeMeasurements', [], 'rund');
            } else {
                $measurementsSet = $this->append($objectMeasurements, 'lido:measurementsSet');
                $this->append($measurementsSet, 'lido:measurementType', [], 'Höhe');
                $this->append($measurementsSet, 'lido:measurementUnit', [], 'mm');
                $this->append($measurementsSet, 'lido:measurementValue', [], $blatt->getPlatteh());
                $measurementsSet = $this->append($objectMeasurements, 'lido:measurementsSet');
                $this->append($measurementsSet, 'lido:measurementType', [], 'Breite');
                $this->append($measurementsSet, 'lido:measurementUnit', [], 'mm');
                $this->append($measurementsSet, 'lido:measurementValue', [], $blatt->getPlattew());
                $this->append($objectMeasurements, 'lido:extentMeasurements', [], $label);
            }
        }

        if ($blatt->getBlatth() or $blatt->getBlattw()) {
            // Werte für die Maße von Druckgraphik und Zeichnungen fließen
            // bei der Erfassung in zwei Listen zusammen; Problem ist,
            // dass die Logik der Zuordnung gegenläufig ist; bei der
            // Druckgraphik ist das Maß "Platte" das kleinere, das Maß
            // "Blatt" das größere, bei den Zeichnungen ist das Maß
            // "Zeichnung" bzw. "Blatt" jedoch das kleinere, ... . Die
            // Werte können also nicht analog zugeordnet werden, sondern
            // müssen gegenläufig zugeordnet werden, allein abhängig vom
            // Objekttyp "Druckgraphik" bzw. "Zeichnung".
            //
            // Oh je...
            if ($blatt->isZeichnung()) {
                if ($blatt->getInstitution() === 2) {
                    $label = 'Zeichnung';
                } else {
                    $label = 'Montierung';
                }
            } else {
                $label = 'Blatt';
            }
            $objectMeasurementsSet = $this->append($objectMeasurementsWrap, 'lido:objectMeasurementsSet');
            $objectMeasurements = $this->append($objectMeasurementsSet, 'lido:objectMeasurements');
            if ($blatt->getDurchmesser()) {
                $measurementsSet = $this->append($objectMeasurements, 'lido:measurementsSet');
                $this->append($measurementsSet, 'lido:measurementType', [], 'Durchmesser');
                $this->append($measurementsSet, 'lido:measurementUnit', [], 'mm');
                $this->append($measurementsSet, 'lido:measurementValue', [], $blatt->getBlatth());
                $this->append($objectMeasurements, 'lido:shapeMeasurements', [], 'rund');
            } else {
                $measurementsSet = $this->append($objectMeasurements, 'lido:measurementsSet');
                $this->append($measurementsSet, 'lido:measurementType', [], 'Höhe');
                $this->append($measurementsSet, 'lido:measurementUnit', [], 'mm');
                $this->append($measurementsSet, 'lido:measurementValue', [], $blatt->getBlatth());
                $measurementsSet = $this->append($objectMeasurements, 'lido:measurementsSet');
                $this->append($measurementsSet, 'lido:measurementType', [], 'Breite');
                $this->append($measurementsSet, 'lido:measurementUnit', [], 'mm');
                $this->append($measurementsSet, 'lido:measurementValue', [], $blatt->getBlattw());
                $this->append($objectMeasurements, 'lido:extentMeasurements', [], $label);
            }
        }
    }

    protected function serializeAdministrativeMetadata (DOMElement $record, Blatt $blatt)
    {
        $administrativeMetadata = $this->append($record, 'lido:administrativeMetadata', ['xml:lang' => 'de']);
        $recordWrap = $this->append($administrativeMetadata, 'lido:recordWrap');

        $this->append($recordWrap, 'lido:recordID', ['lido:type' => 'local'], sprintf('%s', $blatt->getId()));

        $recordType = $this->append($recordWrap, 'lido:recordType');
        $this->append($recordType, 'lido:term', [], 'Einzelobjekt');

        $recordSource = $this->append($recordWrap, 'lido:recordSource');
        $this->appendInstitution($recordSource, $blatt->getInstitutionCode());

        $recordRights = $this->append($recordWrap, 'lido:recordRights');
        $rightsType = $this->append($recordRights, 'lido:rightsType');
        if ($blatt->getInstitutionCode() === 'DE-23') {
            $this->append($rightsType, 'lido:term', null, 'CC0');
        } else {
            $this->append($rightsType, 'lido:term', null, 'Alle Rechte vorbehalten');
        }

        $rightsHolder = $this->append($recordRights, 'lido:rightsHolder');
        $this->appendInstitution($rightsHolder, $blatt->getInstitutionCode());

        $recordInfoSet = $this->append($recordWrap, 'lido:recordInfoSet');

        // $this->append($recordInfoSet, 'lido:recordInfoLink', [
        //     'lido:formatResource' => 'text/html', 'lido:pref' => 'preferred'], $blatt->getPersistentUrl()
        // );

        $metadataDate = $blatt->getAenderung()->format('Y-m-d\TH:i:s\Z');
        $this->append($recordInfoSet, 'lido:recordMetadataDate', null, $metadataDate);

        $resourceWrap = $this->append($administrativeMetadata, 'lido:resourceWrap');

        $resourceSet = $this->append($resourceWrap, 'lido:resourceSet');

        $maxImage = $this->append($resourceSet, 'lido:resourceRepresentation', ['lido:type' => 'display']);
        $this->append($maxImage, 'lido:linkResource', ['lido:formatResource' => 'image/jpeg'], $blatt->getMaxImageUrl());

        $thumbnail = $this->append($resourceSet, 'lido:resourceRepresentation', ['lido:type' => 'thumbnail']);
        $this->append($thumbnail, 'lido:linkResource', ['lido:formatResource' => 'image/jpeg'], $blatt->getThumbnailUrl());

        $purl = $this->append($resourceSet, 'lido:resourceRepresentation', ['lido:type' => 'purl']);
        $this->append($purl, 'lido:linkResource', ['lido:formatResource' => 'text/html'], $blatt->getPersistentUrl());

        $maxImageRights = $this->append($resourceSet, 'lido:rightsResource');
        if ($blatt->getInstitutionCode() === 'DE-23') {
            $this->append(
                $this->append($maxImageRights, 'lido:rightsType'),
                'lido:term',
                null,
                'CC-BY-SA'
            );
        } else {
            $this->append(
                $this->append($maxImageRights, 'lido:rightsType'),
                'lido:term',
                null,
                'Alle Rechte vorbehalten'
            );
        }
        $this->appendInstitution(
            $this->append($maxImageRights, 'lido:rightsHolder'), $blatt->getInstitutionCode()
        );
    }

    protected function append (DOMElement $parent, $qname, $attrs = null, $text = null)
    {
        $child = $parent->appendChild($parent->ownerDocument->createElement($qname));
        if ($attrs) {
            foreach ($attrs as $name => $value) {
                $child->setAttribute($name, $value);
            }
        }
        if ($text) {
            $child->appendChild(new DOMText($text));
        }
        return $child;
    }

    protected function appendInstitution (DOMElement $parent, $isil)
    {
        return $this->append($parent, 'lido:legalBodyID', ['lido:type' => 'code', 'lido:source' => 'isil'], $isil);
    }
}
