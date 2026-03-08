import React, { useState, useRef, useEffect } from 'react'
import { searchLocationsWithDebounce } from '@/lib/nominatim'

interface SearchResult {
  id: string
  name: string
  address: string
  type: string
  lat?: number
  lng?: number
}

interface LocationInputProps {
  label: string
  placeholder: string
  value: string
  onChange: (value: string) => void
  onLocationSelect?: (location: SearchResult) => void
  error?: string
  required?: boolean
}

const LocationInput: React.FC<LocationInputProps> = ({
  label,
  placeholder,
  value,
  onChange,
  onLocationSelect,
  error,
  required = false
}) => {
  const [searchResults, setSearchResults] = useState<SearchResult[]>([])
  const [showResults, setShowResults] = useState(false)
  const inputRef = useRef<HTMLInputElement>(null)
  const dropdownRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (
        dropdownRef.current &&
        !dropdownRef.current.contains(event.target as Node) &&
        inputRef.current &&
        !inputRef.current.contains(event.target as Node)
      ) {
        setShowResults(false)
      }
    }

    document.addEventListener('mousedown', handleClickOutside)
    return () => {
      document.removeEventListener('mousedown', handleClickOutside)
    }
  }, [])

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const inputValue = e.target.value
    onChange(inputValue)

    // Search with debounce
    searchLocationsWithDebounce(inputValue, (results) => {
      setSearchResults(results)
      setShowResults(results.length > 0 && inputValue.length >= 3)
    })
  }

  const handleLocationSelect = (location: SearchResult) => {
    onChange(location.address)
    setShowResults(false)
    setSearchResults([])
    
    if (onLocationSelect) {
      onLocationSelect(location)
    }
  }

  const clearSearch = () => {
    onChange('')
    setSearchResults([])
    setShowResults(false)
    if (inputRef.current) {
      inputRef.current.focus()
    }
  }

  return (
    <div className="relative" ref={dropdownRef}>
      <label htmlFor="location-input" className="block text-sm font-medium text-gray-700">
        {label}
        {required && <span className="text-red-500 ml-1">*</span>}
      </label>
      <div className="mt-1 relative">
        <input
          ref={inputRef}
          type="text"
          id="location-input"
          placeholder={placeholder}
          value={value}
          onChange={handleInputChange}
          onFocus={() => {
            if (searchResults.length > 0 && value.length >= 3) {
              setShowResults(true)
            }
          }}
          className={`block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 ${
            error ? 'border-red-300' : ''
          }`}
          required={required}
        />
        {value && (
          <button
            type="button"
            onClick={clearSearch}
            className="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        )}
      </div>
      {error && (
        <div className="text-red-600 text-sm mt-1">{error}</div>
      )}

      {/* Search Results Dropdown */}
      {showResults && searchResults.length > 0 && (
        <div className="absolute z-50 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 max-h-60 overflow-y-auto">
          {searchResults.map((result, index) => (
            <div
              key={index}
              onClick={() => handleLocationSelect(result)}
              className="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0"
            >
              <div className="font-medium text-gray-900 text-sm">{result.name}</div>
              <div className="text-xs text-gray-600">{result.address}</div>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}

export default LocationInput