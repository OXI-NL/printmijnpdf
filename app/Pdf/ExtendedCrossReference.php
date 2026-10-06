<?php

namespace App\Pdf;

use setasign\Fpdi\PdfParser\CrossReference\CrossReference;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\CrossReference\ReaderInterface;
use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfParser\Type\PdfDictionary;
use setasign\Fpdi\PdfParser\Type\PdfIndirectObject;
use setasign\Fpdi\PdfParser\Type\PdfStream;
use setasign\Fpdi\PdfParser\Type\PdfTypeException;

/**
 * CrossReference van FPDI, aangevuld met cross-reference streams en object
 * streams (PDF 1.5+). Die weigert de gratis parser; veel PDF's uit Word,
 * InDesign en Acrobat gebruiken ze.
 */
class ExtendedCrossReference extends CrossReference
{
    /** @var array<int, array{data: string, first: int, offsets: array<int, int>}> */
    protected array $objectStreams = [];

    protected function readXref($offset)
    {
        $reader = parent::readXref($offset);

        if (!$reader instanceof XrefStreamReader) {
            $trailer = $reader->getTrailer();
            if (isset($trailer->value['XRefStm'])) {
                $stream = parent::readXref($trailer->value['XRefStm']->value + $this->fileHeaderOffset);
                if ($stream instanceof XrefStreamReader) {
                    return new HybridXrefReader($reader, $stream);
                }
            }
        }

        return $reader;
    }

    protected function initReaderInstance($initValue)
    {
        if ($initValue instanceof PdfIndirectObject && $initValue->value instanceof PdfStream) {
            $type = PdfDictionary::get($initValue->value->value, 'Type');
            if ($type->value === 'XRef') {
                $this->checkForEncryption($initValue->value->value);

                return new XrefStreamReader($initValue->value);
            }
        }

        return parent::initReaderInstance($initValue);
    }

    public function getIndirectObject($objectNumber)
    {
        $objectNumber = (int) $objectNumber;

        // Nieuwste sectie eerst; de eerste die het object kent, wint
        foreach ($this->getReaders() as $reader) {
            $entry = $this->entryFor($reader, $objectNumber);

            if ($entry === null || $entry[0] === 0) {
                continue;
            }

            return $entry[0] === 1
                ? $this->readObjectAt($entry[1], $objectNumber)
                : $this->readCompressedObject($objectNumber, $entry[1]);
        }

        throw new CrossReferenceException(
            sprintf('Object (id:%s) not found.', $objectNumber),
            CrossReferenceException::OBJECT_NOT_FOUND
        );
    }

    public function getOffsetFor($objectNumber)
    {
        foreach ($this->getReaders() as $reader) {
            $entry = $this->entryFor($reader, (int) $objectNumber);
            if ($entry !== null && $entry[0] !== 0) {
                return $entry[0] === 1 ? $entry[1] : false;
            }
        }

        return false;
    }

    /**
     * @return array{int, int, int}|null
     */
    protected function entryFor(ReaderInterface $reader, int $objectNumber): ?array
    {
        if ($reader instanceof XrefStreamReader || $reader instanceof HybridXrefReader) {
            return $reader->getEntry($objectNumber);
        }

        $offset = $reader->getOffsetFor($objectNumber);

        return $offset === false ? null : [1, $offset, 0];
    }

    protected function readObjectAt(int $offset, int $objectNumber): PdfIndirectObject
    {
        $this->parser->getTokenizer()->clearStack();
        $this->parser->getStreamReader()->reset($offset + $this->fileHeaderOffset);

        try {
            $object = $this->parser->readValue(null, PdfIndirectObject::class);
        } catch (PdfTypeException $e) {
            throw new CrossReferenceException(
                sprintf('Object (id:%s) not found at location (%s).', $objectNumber, $offset),
                CrossReferenceException::OBJECT_NOT_FOUND,
                $e
            );
        }

        if ($object->objectNumber !== $objectNumber) {
            throw new CrossReferenceException(
                sprintf('Wrong object found, got %s while %s was expected.', $object->objectNumber, $objectNumber),
                CrossReferenceException::OBJECT_NOT_FOUND
            );
        }

        return $object;
    }

    protected function readCompressedObject(int $objectNumber, int $streamNumber): PdfIndirectObject
    {
        if (!isset($this->objectStreams[$streamNumber])) {
            $stream = PdfStream::ensure($this->getIndirectObject($streamNumber)->value);
            $data = StreamDecoder::decode($stream);
            $first = (int) PdfDictionary::get($stream->value, 'First')->value;

            $header = preg_split('/\s+/', trim(substr($data, 0, $first)));
            $offsets = [];
            for ($i = 0; $i + 1 < count($header); $i += 2) {
                $offsets[(int) $header[$i]] = (int) $header[$i + 1];
            }

            $this->objectStreams[$streamNumber] = ['data' => $data, 'first' => $first, 'offsets' => $offsets];
        }

        $objectStream = $this->objectStreams[$streamNumber];

        if (!isset($objectStream['offsets'][$objectNumber])) {
            throw new CrossReferenceException(
                sprintf('Object (id:%s) not found in object stream %s.', $objectNumber, $streamNumber),
                CrossReferenceException::OBJECT_NOT_FOUND
            );
        }

        $parser = new PdfParser(StreamReader::createByString(
            substr($objectStream['data'], $objectStream['first'] + $objectStream['offsets'][$objectNumber])
        ));

        return PdfIndirectObject::create($objectNumber, 0, $parser->readValue());
    }
}
